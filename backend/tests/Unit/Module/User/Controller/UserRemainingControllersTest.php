<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Controller;

use App\Module\Auth\Application\Workflow\RefreshTokenRevocationService;
use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Application\Workflow\CustomerAddressBookService;
use App\Module\User\Application\Workflow\DeleteAccountService;
use App\Module\User\Application\Workflow\RegisterUserService;
use App\Module\User\Application\Workflow\UpdateProfileService;
use App\Module\User\Application\Workflow\UserPersonalDataAnonymizer;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use App\Module\User\UI\Controller\Address\CreateAddressController;
use App\Module\User\UI\Controller\Address\UpdateAddressController;
use App\Module\User\UI\Controller\DeleteAccountController;
use App\Module\User\UI\Controller\ExportMyPersonalDataController;
use App\Module\User\UI\Controller\RegisterController;
use App\Module\User\UI\Controller\UpdateProfileController;
use App\Module\User\UI\Http\RegistrationRateLimiter;
use App\Shared\Application\UnitOfWork;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserRemainingControllersTest extends TestCase
{
    public function testCreateAndUpdateAddressControllersCoverDefaultAndNotFoundBranches(): void
    {
        $user = $this->user();
        $this->setId($user, 7);

        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly(3))->method('validate')->willReturn(new ConstraintViolationList());
        $validator = new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());

        $repo = $this->getMockBuilder(ShippingAddressRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'findDefaultForUser', 'setDefault', 'findOneForUser'])
            ->getMock();
        $repo->expects(self::exactly(3))->method('save');
        $repo->expects(self::once())->method('findDefaultForUser')->with($user)->willReturn(null);
        $repo->expects(self::exactly(2))->method('setDefault')->with($user, self::isInstanceOf(ShippingAddress::class));
        $address = new ShippingAddress($user, 'Home', '1 rue', '75001', 'Paris');
        $repo->expects(self::exactly(2))->method('findOneForUser')->willReturnOnConsecutiveCalls(null, $address);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('flush');
        $writer = new \App\Module\User\Application\Writer\ShippingAddressWriter($repo, new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($entityManager));

        $addressFormatter = new \App\Module\User\Application\Projection\ShippingAddressFormatter();
        $create = new class($writer, $validator, $addressFormatter, $user) extends CreateAddressController {
            public function __construct(\App\Module\User\Application\Writer\ShippingAddressWriter $writer, DtoValidator $validator, \App\Module\User\Application\Projection\ShippingAddressFormatter $formatter, private User $user)
            {
                parent::__construct($writer, $validator, $formatter);
            }

            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };

        $createdA = json_decode((string) $create(Request::create('/', 'POST', [], [], [], [], json_encode([
            'name' => 'Home',
            'address' => '1 rue',
            'postalCode' => '75001',
            'city' => 'Paris',
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(201, $createdA['code'] ?? 201);

        $createdB = $create(Request::create('/', 'POST', [], [], [], [], json_encode([
            'name' => 'Office',
            'address' => '2 rue',
            'postalCode' => '69000',
            'city' => 'Lyon',
            'isDefault' => true,
        ], JSON_THROW_ON_ERROR)));
        self::assertSame(201, $createdB->getStatusCode());

        $addressBook = new CustomerAddressBookService($repo, $addressFormatter);
        $update = new class($addressBook, $writer, $validator, $addressFormatter, $user) extends UpdateAddressController {
            public function __construct(CustomerAddressBookService $addressBook, \App\Module\User\Application\Writer\ShippingAddressWriter $writer, DtoValidator $validator, \App\Module\User\Application\Projection\ShippingAddressFormatter $formatter, private User $user)
            {
                parent::__construct($addressBook, $writer, $validator, $formatter);
            }

            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        self::assertSame(404, $update(99, Request::create('/', 'PUT', [], [], [], [], '{}'))->getStatusCode());
        $updated = json_decode((string) $update(10, Request::create('/', 'PUT', [], [], [], [], json_encode([
            'name' => 'Updated',
            'address' => '3 rue',
            'postalCode' => '31000',
            'city' => 'Toulouse',
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Updated', $updated['data']['address']['name']);
    }

    public function testUpdateProfileControllerCoversUnauthenticatedSuccessAndBusinessErrors(): void
    {
        $addressRepository = $this->getMockBuilder(ShippingAddressRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findDefaultForUser', 'findFirstForUser'])
            ->getMock();
        $addressRepository->expects(self::once())->method('findDefaultForUser')->willReturn(null);
        $addressRepository->expects(self::once())->method('findFirstForUser')->willReturn(null);
        $profiles = new UserProfileFormatter($addressRepository);
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly(6))->method('validate')->willReturn(new ConstraintViolationList());
        $validator = new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());

        $service = $this->getMockBuilder(UpdateProfileService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['update'])
            ->getMock();
        $user = $this->user();
        $this->setId($user, 5);
        $service->method('update')->willReturnCallback(function () use ($user) {
            static $calls = 0;
            ++$calls;

            return match ($calls) {
                1 => $user,
                2 => throw new UserAlreadyExistsException('dup'),
                3 => throw InvalidBirthDateException::invalid(),
                4 => throw InvalidCurrentPasswordException::invalid(),
                default => throw InvalidProfilePasswordException::empty(),
            };
        });

        $controllerUnauth = new class($service, $validator, $profiles) extends UpdateProfileController {
            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return null;
            }
        };
        try {
            $controllerUnauth(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)));
            self::fail('Expected access denied exception for unauthenticated profile update.');
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException) {
        }

        $controller = new class($service, $validator, $profiles, $user) extends UpdateProfileController {
            public function __construct(UpdateProfileService $u, DtoValidator $v, UserProfileFormatter $p, private User $user)
            {
                parent::__construct($u, $v, $p);
            }

            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };

        $payload = json_decode((string) $controller(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ada@example.com', $payload['data']['email']);
        self::assertSame(409, $controller(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(422, $controller(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(422, $controller(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(422, $controller(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
    }

    public function testDeleteAccountControllerAndRegisterControllerCoverSuccessAndFailures(): void
    {
        $user = $this->user();
        $this->setId($user, 11);

        $orders = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasActiveForUser', 'countByUser', 'findByUser'])
            ->getMock();
        $orders->expects(self::exactly(3))
            ->method('hasActiveForUser')
            ->with($user)
            ->willReturnOnConsecutiveCalls(false, true, false);
        $orders->expects(self::once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(0);
        $orders->expects(self::never())
            ->method('findByUser')
            ->with($user, 1000);

        $tradeIns = $this->createMock(TradeInRequestRepositoryPort::class);
        $tradeIns->expects(self::once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(0);
        $tradeIns->expects(self::never())
            ->method('findByUser')
            ->with($user, 1000);

        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $quotes->expects(self::once())
            ->method('countByCustomerEmail')
            ->with('ada@example.com')
            ->willReturn(0);
        $quotes->expects(self::never())
            ->method('findByCustomerEmail')
            ->with('ada@example.com', 1000);

        $refreshTokens = $this->getMockBuilder(RefreshTokenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['revokeAllForUser'])
            ->getMock();
        $refreshTokens->expects(self::once())->method('revokeAllForUser')->with($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('wrapInTransaction')
            ->willReturnCallback(function (\Closure $operation) {
                static $calls = 0;
                ++$calls;
                if (2 === $calls) {
                    throw new \RuntimeException('db down');
                }

                return $operation();
            });
        $entityManager->expects(self::once())->method('remove')->with($user);
        $entityManager->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $delete = new class($orders, $tradeIns, $quotes, $refreshTokens, new DoctrineUnitOfWork($entityManager), new DoctrineTransactionManager($entityManager), $logger, $user) extends DeleteAccountController {
            public function __construct(OrderRepository $orders, TradeInRequestRepositoryPort $tradeIns, QuoteRepositoryPort $quotes, RefreshTokenRepository $refreshTokens, DoctrineUnitOfWork $persistence, DoctrineTransactionManager $transactions, LoggerInterface $logger, private User $user)
            {
                parent::__construct(new DeleteAccountService(
                    $orders,
                    new RefreshTokenRevocationService($refreshTokens),
                    new UserPersonalDataAnonymizer($orders, $tradeIns, $quotes, $persistence),
                    $persistence,
                    $transactions,
                ), $logger);
            }

            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        self::assertSame(200, $delete()->getStatusCode());
        self::assertSame(409, $delete()->getStatusCode());
        self::assertSame(500, $delete()->getStatusCode());

        $factory = new RateLimiterFactory([
            'id' => 'auth_register',
            'policy' => 'fixed_window',
            'limit' => 1,
            'interval' => '1 hour',
        ], new InMemoryStorage());
        $factory->create((new \App\Shared\Infrastructure\Http\RateLimitKeyFactory())->forRequest(
            Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']),
            'new@example.com',
        ))->consume(1);

        $addressRepository = $this->getMockBuilder(ShippingAddressRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findDefaultForUser', 'findFirstForUser'])
            ->getMock();
        $addressRepository->expects(self::never())->method('findDefaultForUser');
        $addressRepository->expects(self::never())->method('findFirstForUser');
        $profiles = new UserProfileFormatter($addressRepository);
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly(4))->method('validate')->willReturn(new ConstraintViolationList());
        $validator = new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
        $registerService = $this->getMockBuilder(RegisterUserService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();
        $createdUser = $this->user();
        $registerService->method('register')->willReturnCallback(function () use ($createdUser) {
            static $calls = 0;
            ++$calls;

            return match ($calls) {
                1 => throw new UserAlreadyExistsException('dup'),
                2 => throw InvalidBirthDateException::invalid(),
                3 => throw ActivationEmailDeliveryException::deliveryFailed(new \RuntimeException('mail down')),
                default => $createdUser,
            };
        });
        $warnLogger = $this->createMock(LoggerInterface::class);
        $warnLogger->expects(self::once())->method('warning');

        $register = new RegisterController(
            $registerService,
            $validator,
            $warnLogger,
            new RegistrationRateLimiter(new \App\Shared\Infrastructure\Http\RateLimitKeyFactory(), $factory),
        );
        self::assertSame(429, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $duplicateResponse = $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.2'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)));
        self::assertSame(202, $duplicateResponse->getStatusCode());
        self::assertSame(
            'Si l’adresse e-mail peut être utilisée, vous recevrez les instructions de vérification associées.',
            json_decode((string) $duplicateResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['message'],
        );
        self::assertSame(422, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.3'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(503, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.4'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        $createdResponse = $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.5'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)));
        self::assertSame(202, $createdResponse->getStatusCode());
        self::assertSame(
            'Si l’adresse e-mail peut être utilisée, vous recevrez les instructions de vérification associées.',
            json_decode((string) $createdResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['message'],
        );
    }

    public function testUserPersonalDataAnonymizerRewritesUserAndSnapshots(): void
    {
        $user = $this->user();
        $this->setId($user, 44);

        $order = new \App\Module\Order\Domain\Entity\Order('ORD-44', $user);
        $order
            ->setBillingName('Ada Lovelace')
            ->setBillingEmail('ada@example.com')
            ->setBillingAddress('1 Rue A')
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setShippingName('Ada Lovelace')
            ->setShippingAddress('1 Rue A')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris');

        $quote = (new Quote('QUO-44'))
            ->setCustomerName('Ada Lovelace')
            ->setCustomerEmail('ada@example.com')
            ->setCustomerCompany('Analytical Engine')
            ->setCustomerAddress('1 Rue A');

        $tradeIn = new TradeInRequest(
            'TRD-44',
            $user,
            new TradeInApplicant('Ada', 'Lovelace', 'ada@example.com', '0102030405'),
            new TradeInProductSnapshot(
                new TradeInProductIdentity('Phones', 'iPhone'),
                new TradeInPurchase(50000, 2024),
                new TradeInProductCondition('good', true, true, true, 'Excellent condition'),
            ),
            new TradeInEstimate(10000, 15000, null, null),
            new \DateTimeImmutable('2026-08-10T10:00:00+00:00'),
        );

        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::once())->method('findByUser')->with($user, 1000)->willReturn([$order]);

        $tradeIns = $this->createMock(TradeInRequestRepositoryPort::class);
        $tradeIns->expects(self::once())->method('findByUser')->with($user, 1000)->willReturn([$tradeIn]);

        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $quotes->expects(self::once())->method('findByCustomerEmail')->with('ada@example.com', 1000)->willReturn([$quote]);

        $persistence = $this->createMock(UnitOfWork::class);
        $persistence->expects(self::once())->method('persist')->with($user);

        (new UserPersonalDataAnonymizer($orders, $tradeIns, $quotes, $persistence))->anonymize($user);

        self::assertSame('deleted+user-44@privacy.invalid', $user->getEmail());
        self::assertSame('Deleted', $user->getFirstName());
        self::assertSame('Deleted user', $order->getBillingName());
        self::assertNull($order->getBillingEmail());
        self::assertSame('Deleted user', $quote->getCustomerName());
        self::assertNull($quote->getCustomerEmail());
        self::assertSame('Deleted', $tradeIn->getFirstName());
        self::assertSame('[deleted]', $tradeIn->getDescription());
    }

    public function testExportMyPersonalDataControllerBuildsJsonAttachment(): void
    {
        $user = $this->user();
        $this->setId($user, 21);

        $addressRepository = $this->getMockBuilder(ShippingAddressRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findDefaultForUser', 'findFirstForUser'])
            ->getMock();
        $addressRepository->expects(self::once())->method('findDefaultForUser')->with($user)->willReturn(null);
        $addressRepository->expects(self::once())->method('findFirstForUser')->with($user)->willReturn(null);

        $orders = $this->getMockBuilder(OrderRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByUser'])
            ->getMock();
        $orders->expects(self::once())->method('findByUser')->with($user, 1000)->willReturn([]);

        $tradeIns = $this->createMock(TradeInRequestRepositoryPort::class);
        $tradeIns->expects(self::once())->method('findByUser')->with($user, 1000)->willReturn([]);

        $quotes = $this->createMock(QuoteRepositoryPort::class);
        $quotes->expects(self::once())->method('findByCustomerEmail')->with('ada@example.com', 1000)->willReturn([]);

        $controller = new class(
            new UserProfileFormatter($addressRepository),
            $orders,
            $tradeIns,
            $quotes,
            new AttachmentResponseFactory(),
            $user,
        ) extends ExportMyPersonalDataController {
            public function __construct(
                UserProfileFormatter $profiles,
                OrderRepository $orders,
                TradeInRequestRepositoryPort $tradeIns,
                QuoteRepositoryPort $quotes,
                AttachmentResponseFactory $attachments,
                private User $user,
            )
            {
                parent::__construct($profiles, $orders, $tradeIns, $quotes, $attachments);
            }

            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };

        $response = $controller();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ada@example.com', $payload['account']['email']);
        self::assertSame([], $payload['orders']);
        self::assertSame([], $payload['tradeIns']);
        self::assertSame([], $payload['quotes']);
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    /**
     * @return array<string, string>
     */
    private function profilePayload(): array
    {
        return [
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
            'currentPassword' => 'Current1',
            'newPassword' => 'StrongPass1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function registerPayload(): array
    {
        return [
            'email' => 'new@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ];
    }
}
