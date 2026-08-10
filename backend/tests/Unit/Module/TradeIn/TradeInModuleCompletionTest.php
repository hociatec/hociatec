<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
use App\Module\TradeIn\Application\DTO\TradeInClosureInput;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Factory\TradeInNumberGenerator;
use App\Module\TradeIn\Application\Workflow\TradeInClosureService;
use App\Module\TradeIn\Application\Workflow\TradeInNotificationEmailService;
use App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow;
use App\Module\TradeIn\Application\Workflow\TradeInStoreCreditVoucherIssuer;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\TradeIn\Infrastructure\Pdf\TradeInReceiptPdfRenderer;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use App\Module\TradeIn\UI\Controller\CreateMyTradeInController;
use App\Module\TradeIn\UI\Controller\CreatePublicTradeInController;
use App\Module\TradeIn\UI\Controller\DownloadMyTradeInReceiptController;
use App\Module\TradeIn\UI\Controller\ListMyTradeInsController;
use App\Module\TradeIn\UI\Controller\RespondToTradeInOfferController;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
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
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TradeInModuleCompletionTest extends TestCase
{
    public function testCreateControllersCoverRibCatalogAndSuccessBranches(): void
    {
        $user = $this->user([CommunicationPreferences::EMAIL]);
        $this->setId($user, 42);
        $product = $this->product();
        $this->setId($product, 9);
        $products = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $products->method('find')->willReturnMap([[9, null], [10, $product]]);
        $service = $this->tradeInService($this->mockEntityManager(self::any()));

        $tradeInFormatter = new \App\Module\TradeIn\Application\Projection\TradeInFormatter();
        $my = new CreateMyTradeInController($service, $this->validator(2), $products, $tradeInFormatter);
        $my->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $my(Request::create('/', 'POST', $this->payload()))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $my(Request::create('/', 'POST', $this->payload(['catalogProductId' => 9]), [], ['rib' => $this->pdfUpload()]))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $my(Request::create('/', 'POST', $this->payload(['catalogProductId' => 10]), [], ['rib' => $this->pdfUpload()]))->getStatusCode());

        $public = new CreatePublicTradeInController($service, $this->validator(1), $products, $tradeInFormatter);
        $public->setContainer($this->controllerContainer(null));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $public(Request::create('/', 'POST', $this->payload()))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $public(Request::create('/', 'POST', $this->payload(), [], ['rib' => $this->pdfUpload()]))->getStatusCode());

        $publicForUser = new CreatePublicTradeInController($service, $this->validator(1), $products, $tradeInFormatter);
        $publicForUser->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_CREATED, $publicForUser(Request::create('/', 'POST', $this->payload(), [], ['rib' => $this->pdfUpload()]))->getStatusCode());
    }

    public function testUserControllersCoverListDownloadAndOfferResponses(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $other = $this->user([], 'grace@example.com');
        $request = $this->tradeInRequest($user)->setOffer(12000, new \DateTimeImmutable('+1 week'))->setStatus(TradeInStatus::OFFER_SENT);
        $request->setReceiptPath('var/private/trade-ins/receipt.pdf');
        $submitted = $this->tradeInRequest($user, 'TR-2');
        $foreign = $this->tradeInRequest($other, 'TR-3')->setOffer(9000)->setStatus(TradeInStatus::OFFER_SENT);
        $em->persist($user);
        $em->persist($other);
        $em->persist($request);
        $em->persist($submitted);
        $em->persist($foreign);
        $em->flush();
        file_put_contents($this->projectDir().'/var/private/trade-ins/receipt.pdf', '%PDF-receipt');

        $repository = $this->tradeInRepository($em);
        $list = new ListMyTradeInsController($repository, new \App\Module\TradeIn\Application\Projection\TradeInFormatter());
        $list->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_OK, $list()->getStatusCode());

        $download = new DownloadMyTradeInReceiptController($repository, new TradeInPrivateFileStorage($this->projectDir()), new TradeInAccessPolicy(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $download->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_OK, $download((int) $request->getId())->getStatusCode());

        $respond = new RespondToTradeInOfferController($repository, $this->tradeInService($this->mockEntityManager(self::any())), new TradeInAccessPolicy());
        $respond->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $respond((int) $foreign->getId(), 'accept')->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $respond((int) $submitted->getId(), 'accept')->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $respond((int) $request->getId(), 'bogus')->getStatusCode());
        self::assertSame(Response::HTTP_OK, $respond((int) $request->getId(), 'accept')->getStatusCode());
    }

    public function testDownloadRejectsMissingOrForeignReceipt(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $foreign = $this->user([], 'foreign@example.com');
        $request = $this->tradeInRequest($foreign);
        $ownedWithoutReceipt = $this->tradeInRequest($user, 'TR-NO-RECEIPT');
        $em->persist($user);
        $em->persist($foreign);
        $em->persist($request);
        $em->persist($ownedWithoutReceipt);
        $em->flush();

        $download = new DownloadMyTradeInReceiptController($this->tradeInRepository($em), new TradeInPrivateFileStorage($this->projectDir()), new TradeInAccessPolicy(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $download->setContainer($this->controllerContainer($user));

        try {
            $download((int) $ownedWithoutReceipt->getId());
            self::fail('Expected missing receipt exception.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception) {
            self::assertSame('Justificatif indisponible.', $exception->getMessage());
        }

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $download((int) $request->getId());
    }

    public function testTradeInServiceCoversCreateOfferTransitionsAndInvalidStatus(): void
    {
        $service = $this->tradeInService($this->mockEntityManager(self::any()));
        $request = $service->create(TradeInInput::fromArray($this->payload()), null, $this->product(), $this->pdfUpload());
        self::assertNotNull($request->getRibPath());
        $service->setStatus($request, TradeInStatus::UNDER_REVIEW);
        $service->setOffer($request, 15000, new \DateTimeImmutable('+1 week'), 'Offre');
        $service->setStatus($request, TradeInStatus::DECLINED);
        $service->setStatus($request, TradeInStatus::DECLINED);

        foreach (TradeInStatus::cases() as $status) {
            $invalid = $this->tradeInRequest(null)->setStatus($status);
            try {
                $service->setStatus($invalid, TradeInStatus::SUBMITTED === $status ? TradeInStatus::COMPLETED : TradeInStatus::SUBMITTED);
                if (TradeInStatus::SUBMITTED !== $status) {
                    self::fail('Expected invalid transition for '.$status->value);
                }
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('Cette transition est impossible', $exception->getMessage());
            }
        }
    }

    public function testNotificationsCoverAllStatusLabelsOffersSkipAndFailures(): void
    {
        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::exactly(10))->method('send')->with(self::isInstanceOf(Email::class));
        $service = $this->notificationService($mailer);
        foreach (TradeInStatus::cases() as $status) {
            $request = $this->tradeInRequest(null)->setStatus($status);
            if (TradeInStatus::OFFER_SENT === $status) {
                $request->setOffer(12345, new \DateTimeImmutable('+1 week'));
            }
            $service->sendStatusChanged($request);
        }

        $skipMailer = $this->createMock(EmailSender::class);
        $skipMailer->expects(self::never())->method('send');
        $skipUser = $this->user();
        $this->notificationService($skipMailer)->sendStatusChanged($this->tradeInRequest($skipUser));

        $failingMailer = $this->createMock(EmailSender::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $this->notificationService($failingMailer, $logger)->sendCreated($this->tradeInRequest(null));
    }

    public function testClosureServiceValidatesStoresReceiptCompletesAndCreatesVoucher(): void
    {
        $service = $this->closureService();
        $submitted = $this->tradeInRequest(null);
        try {
            $service->close($submitted, new TradeInClosureInput(1000, 'cash', 'pending', null, null));
            self::fail('Expected inspected status.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('inspect', $exception->getMessage());
        }

        $request = $this->tradeInRequest(null)->setStatus(TradeInStatus::INSPECTED);
        $service->close($request, new TradeInClosureInput(1000, 'cash', 'paid', 'TX-1', 'Note'));
        self::assertSame(TradeInStatus::COMPLETED, $request->getStatus());
        self::assertSame('cash', $request->getPaymentMethod());
        self::assertNotNull($request->getReceiptPath());

        $em = $this->entityManager();
        $service = $this->closureService($em);
        $user = $this->user();
        $this->setId($user, 99);
        $em->persist($user);
        $em->flush();
        $storeCredit = $this->tradeInRequest($user)->setStatus(TradeInStatus::COMPLETED);
        $service->close($storeCredit, new TradeInClosureInput(2000, 'store_credit', 'pending', null, null));
        self::assertSame('paid', $storeCredit->getPaymentStatus());
        self::assertNotNull($storeCredit->getVoucherCode());
    }

    public function testClosureServiceRejectsInvalidAmountAndAnonymousStoreCredit(): void
    {
        $service = $this->closureService();
        $request = $this->tradeInRequest(null)->setStatus(TradeInStatus::INSPECTED);
        try {
            $service->close($request, new TradeInClosureInput(0, 'cash', 'paid', null, null));
            self::fail('Expected invalid amount.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('montant final', $exception->getMessage());
        }

        try {
            $service->close($request, new TradeInClosureInput(1000, 'store_credit', 'pending', null, null));
            self::fail('Expected anonymous store credit rejection.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('compte Hociatec', $exception->getMessage());
        }
    }

    private function tradeInService(EntityManagerInterface $em): TradeInRequestWorkflow
    {
        return new TradeInRequestWorkflow(
            new TradeInPersistence($em),
            new TradeInEstimator(),
            new TradeInNumberGenerator(),
            $this->notificationService($this->createMock(EmailSender::class)),
            new TradeInPrivateFileStorage($this->projectDir()),
            new \App\Module\TradeIn\Application\Workflow\TradeInStatusWorkflow(),
        );
    }

    private function notificationService(EmailSender $mailer, ?LoggerInterface $logger = null): TradeInNotificationEmailService
    {
        return new TradeInNotificationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $mailer,
            $logger ?? $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function closureService(?EntityManager $em = null): TradeInClosureService
    {
        $em ??= $this->entityManager();

        return new TradeInClosureService(
            new TradeInPersistence($em),
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

    private function notifier(): UserCommunicationNotifier
    {
        $em = $this->entityManager();

        return \App\Tests\Support\UserCommunicationNotifierFactory::create($this, 
            $this->notificationRepository($em),
            new DoctrineUnitOfWork($em),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function mockEntityManager(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $calls): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($calls)->method('persist');
        $em->expects($calls)->method('flush');

        return $em;
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string, mixed> $override */
    private function payload(array $override = []): array
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

    private function pdfUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'rib');
        self::assertIsString($path);
        file_put_contents($path, '%PDF-1.4 test');

        return new UploadedFile($path, 'rib.pdf', 'application/pdf', null, true);
    }

    private function projectDir(): string
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

    private function fakePython(): string
    {
        $path = $this->projectDir().'/fake-python.bat';
        if (!is_file($path)) {
            file_put_contents($path, "@echo off\r\nif \"%1\"==\"-c\" exit /b 0\r\necho %%PDF-test > \"%4\"\r\nexit /b 0\r\n");
        }

        return $path;
    }

    /** @param list<string> $preferences */
    private function user(array $preferences = [], string $email = 'ada@example.com'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences($preferences);

        return $user;
    }

    private function product(): Product
    {
        return new Product('iPhone', 'iphone', 'SKU-1', 'Desc', 100000, 3, new Category('Phones', 'phones'));
    }

    private function tradeInRequest(?User $user, string $reference = 'TR-1'): TradeInRequest
    {
        return TradeInRequestFactory::submitted($reference, $user, 'Ada', 'Lovelace', 'ada@example.com', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
    }

    private function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
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

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function tradeInRepository(EntityManager $em): TradeInRequestRepository
    {
        return new TradeInRequestRepository($this->registry($em));
    }

    private function voucherRepository(EntityManager $em): VoucherRepository
    {
        return new VoucherRepository($this->registry($em));
    }

    private function notificationRepository(EntityManager $em): AccountNotificationEventRepository
    {
        return new AccountNotificationEventRepository($this->registry($em));
    }

    private function setId(object $entity, int $id): void
    {
        (new \ReflectionObject($entity))->getProperty('id')->setValue($entity, $id);
    }
}
