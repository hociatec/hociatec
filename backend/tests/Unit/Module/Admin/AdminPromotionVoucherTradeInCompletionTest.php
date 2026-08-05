<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin;

use App\Module\Admin\UI\Promotion\Controller\CreatePromotionController;
use App\Module\Admin\UI\Promotion\Controller\DeletePromotionController;
use App\Module\Admin\UI\Promotion\Controller\GetPromotionController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionAudiencesController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionsController;
use App\Module\Admin\UI\Promotion\Controller\UpdatePromotionController;
use App\Module\Admin\UI\TradeIn\Controller\CloseTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\DeleteTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\DownloadTradeInDocumentController;
use App\Module\Admin\UI\TradeIn\Controller\ListTradeInsController;
use App\Module\Admin\UI\TradeIn\Controller\SetTradeInOfferController;
use App\Module\Admin\UI\TradeIn\Controller\ShowTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\UpdateTradeInStatusController;
use App\Module\Admin\Application\TradeIn\DTO\TradeInClosureInput;
use App\Module\Admin\UI\Voucher\Controller\CreateVoucherController;
use App\Module\Admin\UI\Voucher\Controller\DeleteVoucherController;
use App\Module\Admin\UI\Voucher\Controller\GetVoucherController;
use App\Module\Admin\UI\Voucher\Controller\ListVouchersController;
use App\Module\Admin\UI\Voucher\Controller\UpdateVoucherController;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Application\Handler\CreatePromotionHandler;
use App\Module\Promotion\Application\Handler\DeletePromotionHandler;
use App\Module\Promotion\Application\Writer\PromotionDataApplier;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\Promotion\Application\Handler\UpdatePromotionHandler;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Application\Workflow\TradeInClosureService;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationEmailService;
use App\Module\TradeIn\Application\Factory\TradeInNumberGenerator;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Module\TradeIn\Application\Storage\TradeInPrivateFileStorage;
use App\Module\TradeIn\Application\Workflow\TradeInService;
use App\Module\User\Domain\Entity\User;
use App\Module\TradeIn\Infrastructure\Pdf\TradeInReceiptPdfRenderer;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Handler\DeleteVoucherHandler;
use App\Module\Voucher\Application\Handler\UpdateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminPromotionVoucherTradeInCompletionTest extends TestCase
{
    public function testAdminPromotionControllers(): void
    {
        $em = $this->entityManager();
        $persistence = new DoctrineUnitOfWork($em);
        $applier = new PromotionDataApplier();
        $createPromotion = new CreatePromotionHandler($persistence, $applier);
        $updatePromotion = new UpdatePromotionHandler($persistence, $applier);
        $deletePromotion = new DeletePromotionHandler($persistence);
        $repository = new PromotionRepository($this->registry($em));
        $validator = $this->validator(2);
        $formatter = new \App\Module\Promotion\Application\Projection\PromotionFormatter();

        $create = new CreatePromotionController($createPromotion, $validator, $formatter);
        self::assertSame(Response::HTTP_BAD_REQUEST, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        $created = $create($this->jsonRequest($this->promotionPayload()));
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());
        $promotionId = (int) $this->payload($created)['data']['promotion']['id'];

        self::assertSame(Response::HTTP_OK, (new ListPromotionAudiencesController(new PromotionEngine(
            $repository,
            new \App\Module\Promotion\Application\Projection\PromotionFormatter(),
            new \App\Module\Promotion\Application\Provider\PromotionAudienceProvider(),
            new \App\Module\Promotion\Application\Calculator\CartSubtotalCalculator(),
            new \App\Module\Promotion\Application\Calculator\PromotionDiscountCalculator(),
            new \App\Module\Promotion\Application\Policy\PromotionEligibilityPolicy(),
        )))()->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ListPromotionsController($repository, $formatter))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetPromotionController($repository, $formatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetPromotionController($repository, $formatter))($promotionId)->getStatusCode());

        $update = new UpdatePromotionController($repository, $updatePromotion, $validator, $formatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $update(999, $this->jsonRequest($this->promotionPayload(), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($promotionId, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $update($promotionId, $this->jsonRequest($this->promotionPayload(['name' => 'Updated']), 'PUT'))->getStatusCode());

        $delete = new DeletePromotionController($repository, $deletePromotion);
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete($promotionId)->getStatusCode());
    }

    public function testAdminVoucherControllers(): void
    {
        $em = $this->entityManager();
        $repository = new VoucherRepository($this->registry($em));
        $persistence = new DoctrineUnitOfWork($em);
        $payload = new VoucherPayload($repository);
        $createVoucher = new CreateVoucherHandler($persistence, $payload);
        $updateVoucher = new UpdateVoucherHandler($persistence, $payload);
        $deleteVoucher = new DeleteVoucherHandler($persistence);
        $validator = $this->validator(5);
        $formatter = new \App\Module\Voucher\Application\Projection\VoucherFormatter();

        $create = new CreateVoucherController($createVoucher, $validator, $formatter);
        self::assertSame(Response::HTTP_BAD_REQUEST, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $create($this->jsonRequest($this->voucherPayload(['discountValue' => 101])))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $create($this->jsonRequest($this->voucherPayload(['code' => 'BADDATE', 'startsAt' => 'bad', 'endsAt' => ''])))->getStatusCode());
        $created = $create($this->jsonRequest($this->voucherPayload()));
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());
        $voucherId = (int) $this->payload($created)['data']['voucher']['id'];

        self::assertSame(Response::HTTP_OK, (new ListVouchersController($repository, $formatter))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetVoucherController($repository, $formatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetVoucherController($repository, $formatter))($voucherId)->getStatusCode());

        $update = new UpdateVoucherController($repository, $updateVoucher, $validator, $formatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $update(999, $this->jsonRequest($this->voucherPayload(), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($voucherId, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($voucherId, $this->jsonRequest($this->voucherPayload(['startsAt' => '2026-08-10', 'endsAt' => '2026-08-01']), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $update($voucherId, $this->jsonRequest($this->voucherPayload(['name' => 'Voucher updated', 'startsAt' => 'bad', 'endsAt' => '']), 'PUT'))->getStatusCode());

        $delete = new DeleteVoucherController($repository, $deleteVoucher);
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete($voucherId)->getStatusCode());
    }

    public function testAdminTradeInControllers(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $submitted = $this->tradeIn($user, 'TR-ADM-1');
        $underReview = $this->tradeIn($user, 'TR-ADM-2')->setStatus(TradeInStatus::UNDER_REVIEW);
        $accepted = $this->tradeIn($user, 'TR-ADM-3')->setStatus(TradeInStatus::ACCEPTED);
        $inspected = $this->tradeIn($user, 'TR-ADM-4')->setStatus(TradeInStatus::INSPECTED);
        $inspected->setRib('var/private/trade-ins/rib.pdf', 'rib.pdf', 4, hash('sha256', 'rib'));
        foreach ([$user, $submitted, $underReview, $accepted, $inspected] as $entity) {
            $em->persist($entity);
        }
        $em->flush();
        file_put_contents($this->projectDir().'/var/private/trade-ins/rib.pdf', 'rib');

        $repository = new TradeInRequestRepository($this->registry($em));
        $service = $this->tradeInService($em);
        $validator = $this->validator(8);

        $tradeInFormatter = new \App\Module\TradeIn\Application\Projection\TradeInFormatter();
        self::assertSame(Response::HTTP_OK, (new ListTradeInsController($repository, $tradeInFormatter))(Request::create('/?q=TR-ADM&status=submitted'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new ShowTradeInController($repository, $tradeInFormatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ShowTradeInController($repository, $tradeInFormatter))((int) $submitted->getId())->getStatusCode());

        $status = new UpdateTradeInStatusController($repository, $service, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $status(999, $this->jsonRequest(['status' => TradeInStatus::UNDER_REVIEW->value], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status((int) $submitted->getId(), $this->jsonRequest(['status' => TradeInStatus::UNDER_REVIEW->value], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $status((int) $submitted->getId(), $this->jsonRequest(['status' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $status((int) $underReview->getId(), $this->jsonRequest(['status' => TradeInStatus::COMPLETED->value], 'PUT'))->getStatusCode());

        $offer = new SetTradeInOfferController($repository, $service, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $offer(999, $this->jsonRequest(['offerCents' => 1000], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $offer((int) $underReview->getId(), $this->jsonRequest(['offerCents' => 1500, 'offerExpiresAt' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $offer((int) $underReview->getId(), $this->jsonRequest(['offerCents' => 1500, 'offerExpiresAt' => '2026-08-10', 'adminNote' => 'Note'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $offer((int) $accepted->getId(), $this->jsonRequest(['offerCents' => 1500], 'PUT'))->getStatusCode());

        $download = new DownloadTradeInDocumentController($repository, new TradeInPrivateFileStorage($this->projectDir()), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        self::assertSame(Response::HTTP_OK, $download((int) $inspected->getId(), 'rib')->getStatusCode());
        try {
            $download(999, 'rib');
            self::fail('Expected missing trade-in document exception.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            self::assertTrue(true);
        }
        try {
            $download((int) $inspected->getId(), 'receipt');
            self::fail('Expected missing receipt exception.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            self::assertTrue(true);
        }

        $closure = new CloseTradeInController($repository, $this->closureService($em), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $closure(999, $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid'], 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $closure((int) $submitted->getId(), $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid'], 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $closure((int) $inspected->getId(), $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid', 'transactionReference' => 'TX'], 'POST'))->getStatusCode());

        $delete = new DeleteTradeInController($repository, new \App\Module\Admin\Application\TradeIn\Handler\DeleteTradeInRequestHandler(
            $repository,
            new \App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence($em),
        ));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete((int) $submitted->getId())->getStatusCode());
    }

    /** @param array<string,mixed> $override */
    private function promotionPayload(array $override = []): array
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
    private function voucherPayload(array $override = []): array
    {
        return $override + [
            'name' => 'Voucher',
            'code' => 'ADM10',
            'description' => 'Desc',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => 10,
            'isActive' => true,
            'startsAt' => '2026-08-01',
            'endsAt' => '2026-08-31',
        ];
    }

    private function tradeIn(?User $user, string $reference): TradeInRequest
    {
        return new TradeInRequest($reference, $user, 'Ada', 'Lovelace', 'ada@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
    }

    private function tradeInService(EntityManager $em): TradeInService
    {
        return new TradeInService(
            new TradeInPersistence($em),
            new TradeInEstimator(),
            new TradeInNumberGenerator(),
            $this->tradeInNotification(),
            new TradeInPrivateFileStorage($this->projectDir()),
        );
    }

    private function closureService(EntityManager $em): TradeInClosureService
    {
        return new TradeInClosureService(
            new TradeInPersistence($em),
            $this->tradeInService($em),
            new DoctrineUnitOfWork($em),
            new DoctrineTransactionManager($em),
            new TradeInPrivateFileStorage($this->projectDir()),
            new TradeInReceiptPdfRenderer(new AccessiblePdfRenderer($this->projectDir(), $this->fakePython(), '')),
            $this->createVoucherHandler($em),
            new VoucherNotificationEmailService(
                new EmailTemplateRepository($this->registry($em)),
                $this->createMock(MailerInterface::class),
                $this->notifier($em),
                $this->createMock(LoggerInterface::class),
                'https://front.example.test',
                'noreply@example.com',
            ),
            $this->createMock(LoggerInterface::class),
        );
    }

    private function tradeInNotification(): TradeInNotificationEmailService
    {
        return new TradeInNotificationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(MailerInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->notifier($this->entityManager()),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function user(): User
    {
        $user = new User('trade-admin@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function createVoucherHandler(EntityManager $em): CreateVoucherHandler
    {
        $repository = new VoucherRepository($this->registry($em));

        return new CreateVoucherHandler(new DoctrineUnitOfWork($em), new VoucherPayload($repository));
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src'], true);
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

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function projectDir(): string
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

    private function fakePython(): string
    {
        $path = $this->projectDir().'/fake-python.bat';
        if (!is_file($path)) {
            file_put_contents($path, "@echo off\r\nif \"%1\"==\"-c\" exit /b 0\r\necho %%PDF-test > \"%4\"\r\nexit /b 0\r\n");
        }

        return $path;
    }
}
