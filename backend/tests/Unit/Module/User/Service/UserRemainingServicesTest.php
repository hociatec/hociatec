<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Service;

use App\Module\BetaTest\DTO\BetaProfileInput;
use App\Module\BetaTest\Service\BetaTesterProfileService;
use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Module\Notification\Entity\AccountNotificationEvent;
use App\Module\Notification\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Service\CommunicationPreferences;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\User\DTO\RegisterUserInput;
use App\Module\User\DTO\UpdateProfileInput;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\User\Exception\ActivationEmailDeliveryException;
use App\Module\User\Exception\InvalidBirthDateException;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AccountActivationEmailService;
use App\Module\User\Service\AdminCustomerEmailService;
use App\Module\User\Service\ChangeProfileEmailService;
use App\Module\User\Service\ChangeProfilePasswordService;
use App\Module\User\Service\ProfileCurrentPasswordVerifier;
use App\Module\User\Service\RegisterUserService;
use App\Module\User\Service\UpdatePersonalInformationService;
use App\Module\User\Service\UpdateProfileService;
use App\Module\User\Service\UserPersistence;
use App\Module\User\Service\UserProfileFormatter;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRemainingServicesTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testUpdatePersonalInformationServiceHandlesValidInvalidAndFutureBirthDates(): void
    {
        $service = new UpdatePersonalInformationService();
        $user = $this->user();

        $service->update($user, UpdateProfileInput::fromArray([
            'firstName' => 'Grace',
            'lastName' => 'Hopper',
            'email' => 'grace@example.com',
            'birthDate' => '1985-12-09',
            'phoneNumber' => '0607080910',
            'gender' => 'femme',
        ]));
        self::assertSame('Grace', $user->getFirstName());
        self::assertSame('1985-12-09', $user->getBirthDate()->format('Y-m-d'));

        try {
            $service->update($user, UpdateProfileInput::fromArray([
                'firstName' => 'Grace',
                'lastName' => 'Hopper',
                'email' => 'grace@example.com',
                'birthDate' => '1985-99-99',
                'phoneNumber' => '0607080910',
                'gender' => 'femme',
            ]));
            self::fail('Expected invalid birth date exception.');
        } catch (InvalidBirthDateException $exception) {
            self::assertSame('La date de naissance est invalide.', $exception->getMessage());
        }

        try {
            $service->update($user, UpdateProfileInput::fromArray([
                'firstName' => 'Grace',
                'lastName' => 'Hopper',
                'email' => 'grace@example.com',
                'birthDate' => '2099-01-01',
                'phoneNumber' => '0607080910',
                'gender' => 'femme',
            ]));
            self::fail('Expected future birth date exception.');
        } catch (InvalidBirthDateException $exception) {
            self::assertSame('La date de naissance ne peut pas etre dans le futur.', $exception->getMessage());
        }
    }

    public function testUserProfileFormatterUsesDefaultAddressThenFallbackFirstAddress(): void
    {
        $user = $this->user();
        $default = new ShippingAddress($user, 'Home', '1 rue A', '75001', 'Paris');
        $first = new ShippingAddress($user, 'Office', '2 rue B', '69000', 'Lyon');

        $addresses = $this->createMock(ShippingAddressRepository::class);
        $addresses->expects(self::exactly(2))
            ->method('findDefaultForUser')
            ->with($user)
            ->willReturnOnConsecutiveCalls($default, null);
        $addresses->expects(self::once())->method('findFirstForUser')->with($user)->willReturn($first);

        $formatter = new UserProfileFormatter($addresses);

        self::assertSame('1 rue A', $formatter->format($user)['address']);
        self::assertSame('2 rue B', $formatter->format($user)['address']);
    }

    public function testUpdateProfileServiceHandlesHappyPathLogicExceptionAndUniqueViolations(): void
    {
        $user = $this->user();
        $this->setId($user, 9);
        $input = UpdateProfileInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Byron',
            'email' => 'new@example.com',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0607080910',
            'gender' => 'femme',
            'currentPassword' => 'Current1',
            'newPassword' => 'StrongPass1',
        ]);

        $userRepository = $this->createMock(UserRepository::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('isPasswordValid')->willReturn(true);
        $passwordHasher->method('hashPassword')->willReturn('hashed-new');

        $service = new UpdateProfileService(
            $userRepository,
            new UpdatePersonalInformationService(),
            new ChangeProfileEmailService($userRepository, new ProfileCurrentPasswordVerifier($passwordHasher)),
            new ChangeProfilePasswordService($passwordHasher, new ProfileCurrentPasswordVerifier($passwordHasher)),
        );

        $userRepository->expects(self::once())->method('existsByEmailExcludingUser')->with('new@example.com', 9)->willReturn(false);
        $userRepository->expects(self::once())->method('save')->with($user, true);

        $updated = $service->update($user, $input);
        self::assertSame($user, $updated);
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame('hashed-new', $user->getPassword());

        $newUser = $this->user();
        try {
            $service->update($newUser, $input);
            self::fail('Expected logic exception.');
        } catch (\LogicException $exception) {
            self::assertSame('Cannot update the profile of a non-persisted user.', $exception->getMessage());
        }

        $userRepository2 = $this->createMock(UserRepository::class);
        $userRepository2->method('existsByEmailExcludingUser')->willReturn(false);
        $userRepository2->method('save')->willThrowException($this->uniqueConstraint('uniq_users_email'));
        $service2 = new UpdateProfileService(
            $userRepository2,
            new UpdatePersonalInformationService(),
            new ChangeProfileEmailService($userRepository2, new ProfileCurrentPasswordVerifier($passwordHasher)),
            new ChangeProfilePasswordService($passwordHasher, new ProfileCurrentPasswordVerifier($passwordHasher)),
        );

        try {
            $service2->update($user, $input);
            self::fail('Expected duplicate email exception.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertSame('Cet email est deja utilise par un autre compte.', $exception->getMessage());
        }

        $userRepository3 = $this->createMock(UserRepository::class);
        $userRepository3->method('existsByEmailExcludingUser')->willReturn(false);
        $nonEmail = $this->uniqueConstraint('other_unique_key');
        $userRepository3->method('save')->willThrowException($nonEmail);
        $service3 = new UpdateProfileService(
            $userRepository3,
            new UpdatePersonalInformationService(),
            new ChangeProfileEmailService($userRepository3, new ProfileCurrentPasswordVerifier($passwordHasher)),
            new ChangeProfilePasswordService($passwordHasher, new ProfileCurrentPasswordVerifier($passwordHasher)),
        );

        try {
            $service3->update($user, $input);
            self::fail('Expected raw unique constraint exception.');
        } catch (UniqueConstraintViolationException $exception) {
            self::assertSame($nonEmail, $exception);
        }
    }

    public function testAdminCustomerEmailServiceCoversValidationSkipFallbackSuccessAndFailure(): void
    {
        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $fallbackMailer = $this->createMock(MailerInterface::class);
        $fallbackMailer->expects(self::exactly(2))->method('send')->willReturnCallback(
            function (Email $email): void {
                if ('KO' === $email->getSubject()) {
                    throw new \RuntimeException('smtp down');
                }
            }
        );

        $service = new AdminCustomerEmailService(
            $fallbackMailer,
            $logger,
            $this->notifier(),
            'noreply@example.com',
        );

        try {
            $service->send($user, '   ', 'Body');
            self::fail('Expected validation exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Sujet et message sont obligatoires.', $exception->getMessage());
        }

        $service->send($user, 'Sujet', "Line 1\nLine 2");
        self::assertTrue($this->notificationRepository($this->entityManager())->countForUser($user) >= 1);

        try {
            $service->send($user, 'KO', 'Body');
            self::fail('Expected runtime exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Envoi impossible pour le moment. Vérifie la configuration email SMTP.', $exception->getMessage());
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }

        $noEmailUser = $this->persistUser([CommunicationPreferences::NOTIFICATION], 'grace@example.com');
        $fallbackMailer2 = $this->createMock(MailerInterface::class);
        $fallbackMailer2->expects(self::never())->method('send');
        $service2 = new AdminCustomerEmailService(
            $fallbackMailer2,
            $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
        );
        $service2->send($noEmailUser, 'Sujet 2', 'Body 2');
    }

    public function testAdminCustomerEmailServiceSendsHtmlAndTextThroughMailer(): void
    {
        $user = $this->persistUser([CommunicationPreferences::NOTIFICATION, CommunicationPreferences::EMAIL], 'primary@example.com');
        $fallbackMailer = $this->createMock(MailerInterface::class);
        $fallbackMailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'Sujet primaire' === $email->getSubject()
                    && str_contains($email->getHtmlBody() ?? '', 'Body primaire')
                    && str_contains($email->getTextBody() ?? '', 'Body primaire');
            }));

        $service = new AdminCustomerEmailService(
            $fallbackMailer,
            $this->createMock(LoggerInterface::class),
            $this->notifier(),
            'noreply@example.com',
        );

        $service->send($user, 'Sujet primaire', 'Body primaire');
        self::assertTrue($this->notificationRepository($this->entityManager())->countForUser($user) >= 1);
    }

    public function testAccountActivationEmailServiceCoversSuccessRenderFailureAndDeliveryFailure(): void
    {
        $user = $this->user();
        $this->setId($user, 7);

        $templates = $this->createMock(EmailTemplateRepository::class);
        $renderer = new EmailTemplateRenderer($templates);

        $fallbackMailer = $this->createMock(MailerInterface::class);
        $fallbackMailer->expects(self::once())->method('send')->with(self::isInstanceOf(Email::class));
        $service = new AccountActivationEmailService(
            $renderer,
            $fallbackMailer,
            $this->createMock(LoggerInterface::class),
            'https://front.example.test/',
            'noreply@example.com',
        );
        $service->sendActivationEmail($user, 'rawtoken');

        $brokenTemplates = $this->createMock(EmailTemplateRepository::class);
        $brokenTemplates->method('findActiveOneByScenarioKey')->willThrowException(new \RuntimeException('render down'));
        $renderLogger = $this->createMock(LoggerInterface::class);
        $renderLogger->expects(self::once())->method('error');
        $service2 = new AccountActivationEmailService(
            new EmailTemplateRenderer($brokenTemplates),
            $this->createMock(MailerInterface::class),
            $renderLogger,
            'https://front.example.test',
            'noreply@example.com',
        );
        try {
            $service2->sendActivationEmail($user, 'rawtoken');
            self::fail('Expected activation rendering exception.');
        } catch (ActivationEmailDeliveryException $exception) {
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }

        $failingFallback = $this->createMock(MailerInterface::class);
        $failingFallback->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $sendLogger = $this->createMock(LoggerInterface::class);
        $sendLogger->expects(self::once())->method('warning');
        $service3 = new AccountActivationEmailService(
            $renderer,
            $failingFallback,
            $sendLogger,
            'https://front.example.test',
            'noreply@example.com',
        );
        try {
            $service3->sendActivationEmail($user, 'rawtoken');
            self::fail('Expected activation delivery exception.');
        } catch (ActivationEmailDeliveryException $exception) {
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }
    }

    public function testRegisterUserServiceCoversFailuresSuccessAndBetaProfileBranch(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $entityManager->expects(self::atLeast(1))->method('persist');
        $persistence = new UserPersistence($entityManager);
        $betaProfiles = new BetaTesterProfileService(new DoctrinePersistence($entityManager));

        $activationEmails = new AccountActivationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(MailerInterface::class),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );

        $service = new RegisterUserService($userRepository, $hasher, $activationEmails, $persistence, $betaProfiles);

        $existsInput = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        $userRepository->expects(self::exactly(7))
            ->method('existsByEmail')
            ->willReturnOnConsecutiveCalls(true, false, false, false, false, false, false);
        try {
            $service->register($existsInput);
            self::fail('Expected duplicate email exception.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertStringContainsString('ada@example.com', $exception->getMessage());
        }

        foreach ([
            ['birthDate' => '1990-99-99', 'message' => 'La date de naissance est invalide.'],
            ['birthDate' => '2099-01-01', 'message' => 'La date de naissance ne peut pas etre dans le futur.'],
        ] as $case) {
            try {
                $service->register(RegisterUserInput::fromArray([
                    'email' => 'new@example.com',
                    'password' => 'StrongPass1',
                    'confirmPassword' => 'StrongPass1',
                    'firstName' => 'Ada',
                    'lastName' => 'Lovelace',
                    'birthDate' => $case['birthDate'],
                    'phoneNumber' => '0102030405',
                    'gender' => 'femme',
                ]));
                self::fail('Expected birth date exception.');
            } catch (InvalidBirthDateException $exception) {
                self::assertSame($case['message'], $exception->getMessage());
            }
        }

        $user = $service->register(RegisterUserInput::fromArray([
            'email' => 'ok@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]));
        self::assertSame('hashed', $user->getPassword());
        self::assertFalse($user->isVerified());
        self::assertNotNull($user->getVerificationToken());

        $betaInput = RegisterUserInput::fromArray([
            'email' => 'beta@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
            'isBetaTester' => true,
            'availability' => 'weekly',
            'motivation' => 'help',
            'testingExperience' => 'medium',
            'bugDescriptionAbility' => 'high',
            'technicalKnowledge' => 'medium',
            'accessibilityNeed' => 'none',
            'assistiveTools' => ['voiceover'],
            'devices' => ['desktop'],
            'browsers' => ['firefox'],
            'testingTypes' => ['functional'],
        ]);
        self::assertInstanceOf(BetaProfileInput::class, $betaInput->betaProfile);
        $betaUser = $service->register($betaInput);
        self::assertSame('beta@example.com', $betaUser->getEmail());

        $entityManager2 = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager2->method('wrapInTransaction')->willThrowException($this->uniqueConstraint('duplicate users.email'));
        $entityManager2->method('persist');
        $activationEmails2 = new AccountActivationEmailService(
            new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class)),
            $this->createMock(MailerInterface::class),
            $this->createMock(LoggerInterface::class),
            'https://front.example.test',
            'noreply@example.com',
        );
        $dupService = new RegisterUserService(
            $userRepository,
            $hasher,
            $activationEmails2,
            new UserPersistence($entityManager2),
            new BetaTesterProfileService(new DoctrinePersistence($entityManager2)),
        );
        try {
            $dupService->register(RegisterUserInput::fromArray([
                'email' => 'dup@example.com',
                'password' => 'StrongPass1',
                'confirmPassword' => 'StrongPass1',
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'birthDate' => '1990-01-01',
                'phoneNumber' => '0102030405',
                'gender' => 'femme',
            ]));
            self::fail('Expected duplicate email on transaction.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertStringContainsString('dup@example.com', $exception->getMessage());
        }

        $entityManager3 = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $rawUnique = $this->uniqueConstraint('other_unique_key');
        $entityManager3->method('wrapInTransaction')->willThrowException($rawUnique);
        $entityManager3->method('persist');
        $rawUniqueService = new RegisterUserService(
            $userRepository,
            $hasher,
            $activationEmails2,
            new UserPersistence($entityManager3),
            new BetaTesterProfileService(new DoctrinePersistence($entityManager3)),
        );

        try {
            $rawUniqueService->register(RegisterUserInput::fromArray([
                'email' => 'raw@example.com',
                'password' => 'StrongPass1',
                'confirmPassword' => 'StrongPass1',
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
                'birthDate' => '1990-01-01',
                'phoneNumber' => '0102030405',
                'gender' => 'femme',
            ]));
            self::fail('Expected raw unique constraint to be rethrown.');
        } catch (UniqueConstraintViolationException $exception) {
            self::assertSame($rawUnique, $exception);
        }
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(AccountNotificationEvent::class),
        ]);
        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function persistUser(array $preferences, string $email = 'ada@example.com'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences($preferences);
        $this->setId($user, random_int(100, 999));
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function notifier(): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            $this->notificationRepository($this->entityManager()),
            new DoctrinePersistence($this->entityManager()),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    private function notificationRepository(EntityManager $entityManager): AccountNotificationEventRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new AccountNotificationEventRepository($registry);
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


    private function uniqueConstraint(string $message): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            new class($message) extends \RuntimeException implements DriverException {
                public function getSQLState(): ?string
                {
                    return null;
                }
            },
            null,
        );
    }
}
