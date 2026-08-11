<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
use App\Module\TradeIn\Application\Factory\TradeInNumberGenerator;
use App\Module\TradeIn\Application\Workflow\TradeInClosureService;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationEmailService;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationMessageBuilder;
use App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow;
use App\Module\TradeIn\Application\Workflow\TradeInStoreCreditVoucherIssuer;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Pdf\TradeInReceiptPdfRenderer;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class TradeInIntegrationTestCase extends TestCase
{
    protected function tradeInService(EntityManagerInterface $em): TradeInRequestWorkflow
    {
        return new TradeInRequestWorkflow(
            new DoctrineUnitOfWork($em),
            new TradeInEstimator(),
            new TradeInNumberGenerator(),
            $this->notificationService($this->createMock(EmailSender::class)),
            new TradeInPrivateFileStorage($this->projectDir()),
            new \App\Module\TradeIn\Application\Workflow\TradeInStatusWorkflow(),
        );
    }

    protected function notificationService(EmailSender $mailer, ?LoggerInterface $logger = null): TradeInNotificationEmailService
    {
        return new TradeInNotificationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $mailer,
            $logger ?? $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
            new TradeInNotificationMessageBuilder('https://front.example.test'),
        );
    }

    protected function closureService(?EntityManager $em = null): TradeInClosureService
    {
        $em ??= $this->entityManager();

        return new TradeInClosureService(
            new DoctrineUnitOfWork($em),
            $this->tradeInService($em),
            new DoctrineTransactionManager($em),
            new TradeInPrivateFileStorage($this->projectDir()),
            new TradeInReceiptPdfRenderer(new AccessiblePdfRenderer($this->projectDir(), $this->fakePython(), '')),
            new TradeInStoreCreditVoucherIssuer(
                new CreateVoucherHandler(new DoctrineUnitOfWork($em), new VoucherPayload($this->voucherRepository($em))),
                new VoucherNotificationEmailService(
                    new EmailTemplateRepository($this->registry($em)),
                    $this->createMock(EmailSender::class),
                    $this->notifier(),
                    $this->createMock(LoggerInterface::class),
                    'noreply@example.com',
                    \App\Tests\Support\VoucherNotificationRenderingFactory::create(),
                ),
                new DoctrineUnitOfWork($em),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    protected function notifier(): UserCommunicationNotifier
    {
        $em = $this->entityManager();

        return \App\Tests\Support\UserCommunicationNotifierFactory::create(
            $this,
            $this->notificationRepository($em),
            new DoctrineUnitOfWork($em),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    protected function mockEntityManager(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $calls): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($calls)->method('persist');
        $em->expects($calls)->method('flush');

        return $em;
    }

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string, mixed> $override */
    protected function payload(array $override = []): array
    {
        return $override + [
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '0102030405',
            'category' => 'smartphone',
            'productName' => 'iPhone',
            'purchasePriceCents' => 100000,
            'purchaseYear' => 2025,
            'brand' => 'Apple',
            'model' => '15',
            'serialNumber' => 'SN',
            'conditionGrade' => 'bon',
            'functional' => '1',
            'hasAccessories' => '1',
            'hasProofOfPurchase' => '1',
            'description' => 'Bon etat',
            'consent' => '1',
        ];
    }

    protected function pdfUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'rib');
        self::assertIsString($path);
        file_put_contents($path, '%PDF-1.4 test');

        return new UploadedFile($path, 'rib.pdf', 'application/pdf', null, true);
    }

    protected function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-trade-in-tests';
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

    /** @param list<string> $preferences */
    protected function user(array $preferences = [], string $email = 'ada@example.com'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences($preferences);

        return $user;
    }

    protected function product(): Product
    {
        return new Product('iPhone', 'iphone', 'SKU-1', 'Desc', 100000, 3, new Category('Phones', 'phones'));
    }

    protected function tradeInRequest(?User $user, string $reference = 'TR-1'): TradeInRequest
    {
        return TradeInRequestFactory::submitted($reference, $user, 'Ada', 'Lovelace', 'ada@example.com', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
    }

    protected function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    protected function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(TradeInRequest::class),
            $em->getClassMetadata(Voucher::class),
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

    protected function tradeInRepository(EntityManager $em): TradeInRequestRepository
    {
        return new TradeInRequestRepository($this->registry($em));
    }

    protected function voucherRepository(EntityManager $em): VoucherRepository
    {
        return new VoucherRepository($this->registry($em));
    }

    protected function notificationRepository(EntityManager $em): AccountNotificationEventRepository
    {
        return new AccountNotificationEventRepository($this->registry($em));
    }

    protected function setId(object $entity, int $id): void
    {
        (new \ReflectionObject($entity))->getProperty('id')->setValue($entity, $id);
    }
}
