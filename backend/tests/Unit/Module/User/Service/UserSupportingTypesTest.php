<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Service;

use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\User\Application\Service\ChangeProfileEmailService;
use App\Module\User\Application\Service\ChangeProfilePasswordService;
use App\Module\User\Application\Service\ProfileCurrentPasswordVerifier;
use App\Module\User\Application\Service\ShippingAddressFormatter;
use App\Module\User\Application\Service\UserUniqueConstraintViolationDetector;
use App\Module\User\Application\Service\VerificationTokenHasher;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;

final class UserSupportingTypesTest extends TestCase
{
    public function testRegisterUserInputNormalizesAndValidatesCallbacks(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $input = RegisterUserInput::fromArray([
            'email' => '  Ada@Example.COM ',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => ' Ada ',
            'lastName' => ' Lovelace ',
            'birthDate' => '1990-01-01',
            'phoneNumber' => ' 01 02 03 04 05 ',
            'gender' => ' FEMME ',
        ]);

        self::assertSame('ada@example.com', $input->email);
        self::assertSame('Ada', $input->firstName);
        self::assertSame('Lovelace', $input->lastName);
        self::assertSame('1990-01-01', $input->birthDate);
        self::assertSame('01 02 03 04 05', $input->phoneNumber);
        self::assertSame('femme', $input->gender);
        self::assertCount(0, $validator->validate($input));

        $invalidBirth = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-99-99',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        self::assertSame('La date de naissance est invalide.', (string) $validator->validate($invalidBirth)->get(0)->getMessage());

        $futureBirth = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '2099-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        self::assertSame('La date de naissance ne peut pas etre dans le futur.', (string) $validator->validate($futureBirth)->get(0)->getMessage());

        $invalidPhone = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '1990-01-01',
            'phoneNumber' => 'abcdef',
            'gender' => 'femme',
        ]);
        $violations = $validator->validate($invalidPhone);
        self::assertTrue($violations->count() >= 1);
        self::assertContains(
            'Le numero de telephone est invalide.',
            array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations))
        );

        $emptyFields = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'StrongPass1',
            'confirmPassword' => 'StrongPass1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '',
            'phoneNumber' => '',
            'gender' => 'femme',
        ]);
        $emptyViolations = $validator->validate($emptyFields);
        self::assertContains(
            'This value should not be blank.',
            array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($emptyViolations))
        );

        $reflection = new \ReflectionClass(RegisterUserInput::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $constructor = $reflection->getConstructor();
        self::assertInstanceOf(\ReflectionMethod::class, $constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        $stringValue = $reflection->getMethod('stringValue');
        $stringValue->setAccessible(true);
        self::assertSame('', $stringValue->invoke(null, ['email' => 123], 'email'));
    }

    public function testUpdateProfileInputNormalizesPasswordAliasesAndBirthDate(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $input = UpdateProfileInput::fromArray([
            'firstName' => ' Ada ',
            'lastName' => ' Lovelace ',
            'email' => ' Ada@Example.COM ',
            'birthDate' => '1990-01-01',
            'phoneNumber' => ' 0102030405 ',
            'gender' => 'femme',
            'password' => 'StrongPass1',
            'currentPassword' => ' Current1 ',
        ]);

        self::assertSame('Ada', $input->firstName);
        self::assertSame('Lovelace', $input->lastName);
        self::assertSame('ada@example.com', $input->email);
        self::assertSame('StrongPass1', $input->newPassword);
        self::assertSame(' Current1 ', $input->currentPassword);
        self::assertCount(0, $validator->validate($input));

        $invalidBirth = UpdateProfileInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'birthDate' => '1990-99-99',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        self::assertSame('La date de naissance est invalide.', (string) $validator->validate($invalidBirth)->get(0)->getMessage());

        $futureBirth = UpdateProfileInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'birthDate' => '2099-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        self::assertSame('La date de naissance ne peut pas etre dans le futur.', (string) $validator->validate($futureBirth)->get(0)->getMessage());
    }

    public function testUserExceptionsExposeMessagesAndStatusCodes(): void
    {
        $activation = ActivationEmailDeliveryException::deliveryFailed(new \RuntimeException('smtp down'));
        self::assertSame("L'e-mail d'activation n'a pas pu etre envoye.", $activation->getMessage());
        self::assertSame(503, $activation->getStatusCode());

        $alreadyExists = UserAlreadyExistsException::forEmail('ada@example.com');
        self::assertStringContainsString('ada@example.com', $alreadyExists->getMessage());
        self::assertSame(409, $alreadyExists->getStatusCode());

        self::assertSame(
            'Le mot de passe actuel est obligatoire pour cette modification.',
            InvalidCurrentPasswordException::missing()->getMessage()
        );
        self::assertSame(
            'Le mot de passe actuel est incorrect.',
            InvalidCurrentPasswordException::invalid()->getMessage()
        );
        self::assertSame(
            'Le nouveau mot de passe ne peut pas etre vide.',
            InvalidProfilePasswordException::empty()->getMessage()
        );
    }

    public function testProfilePasswordAndEmailServicesCoverNoopAndValidationPaths(): void
    {
        $user = $this->user();
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturnCallback(static fn (User $user, ?string $password): bool => 'Current1' === $password);
        $hasher->method('hashPassword')->willReturn('hashed-new');
        $verifier = new ProfileCurrentPasswordVerifier($hasher);

        $verifier->verify($user, 'Current1');

        try {
            $verifier->verify($user, null);
            self::fail('Expected missing current password exception.');
        } catch (InvalidCurrentPasswordException $exception) {
            self::assertSame('Le mot de passe actuel est obligatoire pour cette modification.', $exception->getMessage());
        }

        try {
            $verifier->verify($user, 'Wrong1');
            self::fail('Expected invalid current password exception.');
        } catch (InvalidCurrentPasswordException $exception) {
            self::assertSame('Le mot de passe actuel est incorrect.', $exception->getMessage());
        }

        $passwords = new ChangeProfilePasswordService($hasher, $verifier);
        $originalPassword = $user->getPassword();
        $passwords->change($user, null, null);
        self::assertSame($originalPassword, $user->getPassword());

        try {
            $passwords->change($user, '   ', 'Current1');
            self::fail('Expected invalid profile password exception.');
        } catch (InvalidProfilePasswordException $exception) {
            self::assertSame('Le nouveau mot de passe ne peut pas etre vide.', $exception->getMessage());
        }

        $passwords->change($user, 'StrongPass2', 'Current1');
        self::assertSame('hashed-new', $user->getPassword());

        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('existsByEmailExcludingUser')->with('other@example.com', 7)->willReturn(true);
        $emails = new ChangeProfileEmailService($repository, $verifier);

        $emails->change($user, 7, $user->getEmail(), null);
        self::assertSame('ada@example.com', $user->getEmail());

        try {
            $emails->change($user, 7, 'other@example.com', 'Current1');
            self::fail('Expected duplicate email exception.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertSame('Cet email est deja utilise par un autre compte.', $exception->getMessage());
        }
    }

    public function testStaticHelpersCoverUtilityBranchesAndPrivateConstructors(): void
    {
        $rawToken = VerificationTokenHasher::generateRawToken();
        self::assertSame(64, strlen($rawToken));
        self::assertTrue(VerificationTokenHasher::isValidRawToken($rawToken));
        self::assertSame(hash('sha256', 'abc'), VerificationTokenHasher::hash('abc'));
        self::assertFalse(VerificationTokenHasher::isValidRawToken('xyz'));

        self::assertTrue(UserUniqueConstraintViolationDetector::isEmail($this->uniqueConstraint('UNIQ_USERS_EMAIL')));
        self::assertTrue(UserUniqueConstraintViolationDetector::isEmail($this->uniqueConstraint('Duplicate entry for email')));
        self::assertFalse(UserUniqueConstraintViolationDetector::isEmail($this->uniqueConstraint('other_unique_key')));

        $user = $this->user();
        $address = new ShippingAddress($user, 'Home', '1 rue', '75001', 'Paris');
        self::assertSame('Home', ShippingAddressFormatter::toArray($address)['name']);

        foreach ([
            VerificationTokenHasher::class,
            UserUniqueConstraintViolationDetector::class,
            ShippingAddressFormatter::class,
        ] as $className) {
            $reflection = new \ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor = $reflection->getConstructor();
            self::assertInstanceOf(\ReflectionMethod::class, $constructor);
            $constructor->setAccessible(true);
            $constructor->invoke($instance);
        }
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
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
            null
        );
    }
}
