<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Controller;

use App\Module\User\UI\Controller\Address\CreateAddressController;
use App\Module\User\UI\Controller\Address\UpdateAddressController;
use App\Module\User\UI\Controller\DeleteAccountController;
use App\Module\User\UI\Controller\RegisterController;
use App\Module\User\Application\Service\RegistrationRateLimiter;
use App\Module\User\UI\Controller\UpdateProfileController;
use App\Module\Auth\Infrastructure\Repository\RefreshTokenRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use App\Module\User\Application\Service\DeleteAccountService;
use App\Module\User\Application\Service\RegisterUserService;
use App\Module\User\Application\Service\UpdateProfileService;
use App\Module\User\Application\Service\UserPersistence;
use App\Module\User\Application\Service\UserProfileFormatter;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
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
        $writer = new \App\Module\User\Application\Service\ShippingAddressWriter($repo, new \App\Infrastructure\Persistence\DoctrineUnitOfWork($entityManager));

        $create = new class($writer, $validator, $user) extends CreateAddressController {
            public function __construct(\App\Module\User\Application\Service\ShippingAddressWriter $writer, DtoValidator $validator, private User $user)
            {
                parent::__construct($writer, $validator);
            }
            protected function getUser(): ?User { return $this->user; }
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

        $update = new class($repo, $writer, $validator, $user) extends UpdateAddressController {
            public function __construct(ShippingAddressRepository $addresses, \App\Module\User\Application\Service\ShippingAddressWriter $writer, DtoValidator $validator, private User $user)
            {
                parent::__construct($addresses, $writer, $validator);
            }
            protected function getUser(): ?User { return $this->user; }
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
        $symfonyValidator->expects(self::exactly(5))->method('validate')->willReturn(new ConstraintViolationList());
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
            protected function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface { return null; }
        };
        self::assertSame(401, $controllerUnauth(Request::create('/', 'PUT', [], [], [], [], json_encode($this->profilePayload(), JSON_THROW_ON_ERROR)))->getStatusCode());

        $controller = new class($service, $validator, $profiles, $user) extends UpdateProfileController {
            public function __construct(UpdateProfileService $u, DtoValidator $v, UserProfileFormatter $p, private User $user)
            {
                parent::__construct($u, $v, $p);
            }
            protected function getUser(): ?User { return $this->user; }
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
            ->onlyMethods(['hasActiveForUser'])
            ->getMock();
        $orders->expects(self::exactly(3))
            ->method('hasActiveForUser')
            ->with($user)
            ->willReturnOnConsecutiveCalls(false, true, false);

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
        $entityManager->expects(self::once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $delete = new class($orders, $refreshTokens, new UserPersistence($entityManager), new DoctrineTransactionManager($entityManager), $logger, $user) extends DeleteAccountController {
            public function __construct(OrderRepository $orders, RefreshTokenRepository $refreshTokens, UserPersistence $persistence, DoctrineTransactionManager $transactions, LoggerInterface $logger, private User $user)
            {
                parent::__construct(new DeleteAccountService($orders, $refreshTokens, $persistence, $transactions), $logger);
            }
            protected function getUser(): ?User { return $this->user; }
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
        $factory->create((new \App\Infrastructure\Http\RateLimitKeyFactory())->forRequest(
            Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']),
            'new@example.com',
        ))->consume(1);

        $addressRepository = $this->getMockBuilder(ShippingAddressRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findDefaultForUser', 'findFirstForUser'])
            ->getMock();
        $addressRepository->expects(self::once())->method('findDefaultForUser')->willReturn(null);
        $addressRepository->expects(self::once())->method('findFirstForUser')->willReturn(null);
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
            $profiles,
            new RegistrationRateLimiter(new \App\Infrastructure\Http\RateLimitKeyFactory(), $factory),
        );
        self::assertSame(429, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(409, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.2'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(422, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.3'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(503, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.4'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(201, $register(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.5'], json_encode($this->registerPayload(), JSON_THROW_ON_ERROR)))->getStatusCode());
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
