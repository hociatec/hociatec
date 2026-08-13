<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Controller;

use App\Module\Admin\Application\User\Provider\CustomerDetailsProvider;
use App\Module\Admin\UI\User\Controller\CreateCustomerVoucherController;
use App\Module\Admin\UI\User\Controller\ListCustomersController;
use App\Module\Admin\UI\User\Controller\SendCustomerEmailController;
use App\Module\Admin\UI\User\Controller\ShowCustomerController;
use App\Module\Admin\UI\User\Controller\UpdateCustomerAdminProfileController;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Application\Workflow\AdminCustomerEmailService;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
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
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminUserControllersTest extends TestCase
{
    public function testListCustomersControllerBuildsPaginatedResponse(): void
    {
        $users = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['countAdminCustomerRows', 'findAdminCustomerRows'])
            ->getMock();
        $users->expects(self::once())->method('countAdminCustomerRows')->with('Ada')->willReturn(1);
        $users->expects(self::once())
            ->method('findAdminCustomerRows')
            ->with('Ada', 'name_asc', 10, 10)
            ->willReturn([['email' => 'ada@example.com']]);

        $response = (new ListCustomersController($users))(Request::create('/?q=Ada&sort=name_asc&page=2&perPage=10'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('ada@example.com', $payload['data']['items'][0]['email']);
        self::assertSame(2, $payload['data']['meta']['page']);
    }

    public function testShowCustomerControllerHandlesNotFoundAndFormatsCustomerDetails(): void
    {
        $user = $this->user();
        $this->setId($user, 42);
        $user->setAdminNotes('VIP')->setAdminTags(['b2b']);

        $order = (new Order('ORD-1', $user))->setTotalPriceCents(2500);
        $address = new ShippingAddress($user, 'Home', '1 rue', '75001', 'Paris');
        $voucher = new Voucher('Gift', 'GIFT10', Voucher::TYPE_FIXED_CENTS, 1000);

        $users = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
        $users->expects(self::exactly(2))->method('find')->with(42)->willReturnOnConsecutiveCalls(null, $user);
        $addresses = $this->getMockBuilder(ShippingAddressRepository::class)->disableOriginalConstructor()->onlyMethods(['findAllForUser'])->getMock();
        $addresses->expects(self::once())->method('findAllForUser')->with($user)->willReturn([$address]);
        $orders = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByUser', 'findForUserList', 'countForUserList', 'countStatusBucketsForUser'])
            ->getMock();
        $orders->expects(self::once())->method('findByUser')->with($user, 1000, 0)->willReturn([$order]);
        $orders->expects(self::once())->method('countForUserList')->with($user, 'all', null)->willReturn(1);
        $orders->expects(self::once())->method('findForUserList')->with($user, 'all', null, 10, 0)->willReturn([$order]);
        $orders->expects(self::once())->method('countStatusBucketsForUser')->with($user)->willReturn([
            'all' => 1,
            'open' => 1,
            'delivered' => 0,
            'cancelled' => 0,
        ]);
        $voucherEntityManager = $this->entityManager([Voucher::class]);
        $voucher->setRecipientUserId(42);
        $voucherEntityManager->persist($voucher);
        $voucherEntityManager->flush();
        $vouchers = $this->voucherRepository($voucherEntityManager);

        $controller = new ShowCustomerController(new CustomerDetailsProvider(
            $users,
            $addresses,
            $orders,
            $vouchers,
            \App\Tests\Support\OrderFormatterFactory::create(),
            new \App\Module\User\Application\Projection\ShippingAddressFormatter(),
            new \App\Module\Voucher\Application\Projection\VoucherFormatter(),
        ));

        $showRequest = Request::create('/');

        self::assertSame(Response::HTTP_NOT_FOUND, $controller($showRequest, 42)->getStatusCode());

        $payload = json_decode((string) $controller($showRequest, 42)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Ada Lovelace', $payload['data']['customer']['fullName']);
        self::assertSame(2500, $payload['data']['customer']['totalSpentCents']);
        self::assertSame('ORD-1', $payload['data']['customer']['lastOrderNumber']);
        self::assertSame('Home', $payload['data']['addresses'][0]['name']);
        self::assertSame('GIFT10', $payload['data']['vouchers'][0]['code']);
    }

    public function testUpdateCustomerAdminProfileControllerCoversPayloadBranchesAndSuccess(): void
    {
        $user = $this->user();
        $this->setId($user, 42);

        $users = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['find', 'save'])->getMock();
        $users->expects(self::exactly(4))->method('find')->with(42)->willReturnOnConsecutiveCalls(null, $user, $user, $user);
        $users->expects(self::exactly(2))->method('save')->with($user);

        $controller = new UpdateCustomerAdminProfileController(
            $users,
            new \App\Module\Admin\Application\User\Handler\UpdateCustomerAdminProfileHandler($users, new DoctrineUnitOfWork($this->entityManager([User::class]))),
            $this->validator(2),
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(42, Request::create('/', 'PATCH'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(42, Request::create('/', 'PATCH', [], [], [], [], '{'))->getStatusCode());

        $empty = json_decode((string) $controller(42, Request::create('/', 'PATCH'))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($empty['data']['customer']['adminNotes']);

        $updated = json_decode((string) $controller(42, Request::create('/', 'PATCH', [], [], [], [], json_encode([
            'adminNotes' => ' Note ',
            'adminTags' => [' vip ', ''],
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Note', $updated['data']['customer']['adminNotes']);
        self::assertSame(['vip'], $updated['data']['customer']['adminTags']);
    }

    public function testSendCustomerEmailControllerCoversErrorsAndSuccess(): void
    {
        $user = $this->user();
        $this->setId($user, 42);

        $users = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
        $users->expects(self::exactly(5))->method('find')->with(42)->willReturnOnConsecutiveCalls(null, $user, $user, $user, $user);

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $service = new AdminCustomerEmailService(
            $mailer,
            $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
        );

        $controller = new SendCustomerEmailController($users, $service, $this->validator(3));

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(42, Request::create('/', 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(42, Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'subject' => '',
            'message' => 'Body',
        ], JSON_THROW_ON_ERROR)))->getStatusCode());

        $user->setCommunicationPreferences(['email']);
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'subject' => 'Hello',
            'message' => 'Body',
        ], JSON_THROW_ON_ERROR)))->getStatusCode());

        $user->setCommunicationPreferences([]);
        self::assertSame(Response::HTTP_OK, $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'subject' => 'Hello',
            'message' => 'Body',
        ], JSON_THROW_ON_ERROR)))->getStatusCode());
    }

    public function testCreateCustomerVoucherControllerCoversErrorsGeneratedCodeAndEmailBranch(): void
    {
        $user = $this->user();
        $this->setId($user, 42);
        $user->setCommunicationPreferences([]);

        $users = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
        $users->expects(self::exactly(5))->method('find')->with(42)->willReturnOnConsecutiveCalls(null, $user, $user, $user, $user);

        $entityManager = $this->entityManager([Voucher::class]);
        $voucherRepository = $this->voucherRepository($entityManager);
        $manager = new CreateVoucherHandler(new DoctrineUnitOfWork($entityManager), new VoucherPayload($voucherRepository));
        $customerVoucherHandler = new \App\Module\Admin\Application\User\Handler\CreateCustomerVoucherHandler(
            $manager,
            $this->voucherNotifications(),
            $voucherRepository,
            new DoctrineUnitOfWork($entityManager),
        );

        $controller = new CreateCustomerVoucherController(
            $users,
            $customerVoucherHandler,
            $this->validator(3),
            new \App\Module\Voucher\Application\Projection\VoucherFormatter(),
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(42, Request::create('/', 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(42, Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'name' => '',
            'discountValue' => 0,
        ], JSON_THROW_ON_ERROR)))->getStatusCode());

        $generated = json_decode((string) $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'name' => 'Credit',
            'description' => 'Store credit',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 1200,
            'sendEmail' => false,
            'startsAt' => 'not a date',
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($generated['data']['emailSent']);
        self::assertStringStartsWith('LOVELACE-', $generated['data']['voucher']['code']);

        $emailed = json_decode((string) $controller(42, Request::create('/', 'POST', [], [], [], [], json_encode([
            'name' => 'Gift',
            'code' => ' gift-10 ',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => 1000,
            'sendEmail' => true,
            'startsAt' => '2026-01-01T00:00:00+00:00',
            'endsAt' => '2026-12-31T00:00:00+00:00',
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($emailed['data']['emailSent']);
        self::assertSame('GIFT-10', $emailed['data']['voucher']['code']);
        self::assertNotNull($emailed['data']['voucher']['sentAt']);
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    private function notifier(): UserCommunicationNotifier
    {
        $entityManager = $this->entityManager([User::class, AccountNotificationEvent::class]);
        $notifications = $this->notificationRepository($entityManager);

        return \App\Tests\Support\UserCommunicationNotifierFactory::create($this, 
            $notifications,
            new DoctrineUnitOfWork($entityManager),
            $this->createMock(EmailSender::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function voucherNotifications(): VoucherNotificationEmailService
    {
        $templates = $this->getMockBuilder(EmailTemplateRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findActiveOneByScenarioKey'])
            ->getMock();
        $templates->expects(self::never())->method('findActiveOneByScenarioKey');

        return new VoucherNotificationEmailService(
            $templates,
            $this->createMock(EmailSender::class),
            $this->notifier(),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            \App\Tests\Support\VoucherNotificationRenderingFactory::create(),
        );
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    /**
     * @param list<class-string> $classes
     */
    private function entityManager(array $classes): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema(array_map(
            static fn (string $class) => $entityManager->getClassMetadata($class),
            $classes,
        ));

        return $entityManager;
    }

    private function voucherRepository(EntityManager $entityManager): VoucherRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new VoucherRepository($registry);
    }

    private function notificationRepository(EntityManager $entityManager): AccountNotificationEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new AccountNotificationEventRepository($registry);
    }
}
