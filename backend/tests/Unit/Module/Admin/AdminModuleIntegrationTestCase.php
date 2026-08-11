<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin;

use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
use App\Module\TradeIn\Application\Factory\TradeInNumberGenerator;
use App\Module\TradeIn\Application\Workflow\TradeInClosureService;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationEmailService;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationMessageBuilder;
use App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow;
use App\Module\TradeIn\Application\Workflow\TradeInStoreCreditVoucherIssuer;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Pdf\TradeInReceiptPdfRenderer;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Handler\DeleteVoucherHandler;
use App\Module\Voucher\Application\Handler\UpdateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use App\Tests\Support\TradeInRequestFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AdminModuleIntegrationTestCase extends TestCase
{
    /** @param array<string,mixed> $override */
    protected function promotionPayload(array $override = []): array
    {
        return $override + [
            'name' => 'Promo',
            'slug' => 'promo',
            'discountType' => Promotion::TYPE_PERCENT,
            'discountValue' => 10,
            'audienceKey' => 'all_users',
            'criteria' => ['minimumCartTotalCents' => 1000],
            'description' => 'Desc',
            'isActive' => true,
        ];
    }

    /** @param array<string,mixed> $override */
    protected function voucherPayload(array $override = []): array
    {
        return $override + [
            'name' => 'Voucher',
            'code' => 'ADM10',
            'description' => 'Desc',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => 10,
            'isActive' => true,
            'startsAt' => '2026-08-12',
            'endsAt' => '2026-08-31',
        ];
    }

    protected function tradeIn(?User $user, string $reference): TradeInRequest
    {
        return TradeInRequestFactory::submitted($reference, $user, 'Ada', 'Lovelace', 'ada@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-08-12T10:00:00+00:00'));
    }

    protected function tradeInService(EntityManager $em): TradeInRequestWorkflow
    {
        return new TradeInRequestWorkflow(
            new DoctrineUnitOfWork($em),
            new TradeInEstimator(),
            new TradeInNumberGenerator(),
            $this->tradeInNotification(),
            new TradeInPrivateFileStorage($this->projectDir()),
            new \App\Module\TradeIn\Application\Workflow\TradeInStatusWorkflow(),
        );
    }

    protected function closureService(EntityManager $em): TradeInClosureService
    {
        return new TradeInClosureService(
            new DoctrineUnitOfWork($em),
            $this->tradeInService($em),
            new DoctrineTransactionManager($em),
            new TradeInPrivateFileStorage($this->projectDir()),
            new TradeInReceiptPdfRenderer(new AccessiblePdfRenderer($this->projectDir(), $this->fakePython(), '')),
            new TradeInStoreCreditVoucherIssuer(
                $this->createVoucherHandler($em),
                new VoucherNotificationEmailService(
                    new EmailTemplateRepository($this->registry($em)),
                    $this->createMock(EmailSender::class),
                    $this->notifier($em),
                    $this->createMock(LoggerInterface::class),
                    'noreply@example.com',
                    \App\Tests\Support\VoucherNotificationRenderingFactory::create(),
                ),
                new DoctrineUnitOfWork($em),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    protected function tradeInNotification(): TradeInNotificationEmailService
    {
        return new TradeInNotificationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(EmailSender::class),
            $this->createMock(LoggerInterface::class),
            $this->notifier($this->entityManager()),
            'noreply@example.com',
            new TradeInNotificationMessageBuilder('https://front.example.test'),
        );
    }

    protected function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return \App\Tests\Support\UserCommunicationNotifierFactory::create(
            $this,
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    protected function user(): User
    {
        $user = new User('trade-admin@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    protected function createVoucherHandler(EntityManager $em): CreateVoucherHandler
    {
        $repository = new VoucherRepository($this->registry($em));

        return new CreateVoucherHandler(new DoctrineUnitOfWork($em), new VoucherPayload($repository));
    }

    protected function updateVoucherHandler(EntityManager $em): UpdateVoucherHandler
    {
        $repository = new VoucherRepository($this->registry($em));

        return new UpdateVoucherHandler(new DoctrineUnitOfWork($em), new VoucherPayload($repository));
    }

    protected function deleteVoucherHandler(EntityManager $em): DeleteVoucherHandler
    {
        return new DeleteVoucherHandler(new DoctrineUnitOfWork($em));
    }

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    protected function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    protected function payload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(Promotion::class),
            $em->getClassMetadata(Voucher::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(TradeInRequest::class),
            $em->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $em;
    }

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-admin-pvt-tests';
        if (!is_dir($dir.'/bin')) {
            mkdir($dir.'/bin', 0777, true);
        }
        if (!is_dir($dir.'/var/private/trade-ins')) {
            mkdir($dir.'/var/private/trade-ins', 0777, true);
        }
        if (!is_file($dir.'/bin/render_accessible_pdf.py')) {
            file_put_contents($dir.'/bin/render_accessible_pdf.py', '# fake');
        }

        return $dir;
    }

    protected function fakePython(): string
    {
        $path = $this->projectDir().'/fake-python.bat';
        if (!is_file($path)) {
            file_put_contents($path, "@echo off\r\nif \"%1\"==\"-c\" exit /b 0\r\necho %%PDF-test > \"%4\"\r\nexit /b 0\r\n");
        }

        return $path;
    }
}
