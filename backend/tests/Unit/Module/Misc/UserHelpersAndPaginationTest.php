<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Workflow\ChangeProfilePasswordService;
use App\Module\User\Application\Mapper\ProfileCurrentPasswordVerifier;
use App\Module\User\Application\Mapper\UserUniqueConstraintViolationDetector;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Shared\Infrastructure\Http\Pagination;

final class UserHelpersAndPaginationTest extends TestCase
{
    public function testProfileCurrentPasswordVerifierHandlesMissingInvalidAndValidPasswords(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $verifier = new ProfileCurrentPasswordVerifier($hasher);
        $user = $this->user();

        try {
            $verifier->verify($user, '   ');
            self::fail('Expected missing password exception.');
        } catch (InvalidCurrentPasswordException $exception) {
            self::assertSame('Le mot de passe actuel est obligatoire pour cette modification.', $exception->getMessage());
        }

        $hasher->expects(self::exactly(2))
            ->method('isPasswordValid')
            ->with($user, 'secret')
            ->willReturnOnConsecutiveCalls(false, true);

        try {
            $verifier->verify($user, 'secret');
            self::fail('Expected invalid password exception.');
        } catch (InvalidCurrentPasswordException $exception) {
            self::assertSame('Le mot de passe actuel est incorrect.', $exception->getMessage());
        }

        $verifier->verify($user, 'secret');
        self::assertTrue(true);
    }

    public function testChangeProfilePasswordServiceSupportsNullRejectsBlankAndHashesPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $verifierHasher = $this->createMock(UserPasswordHasherInterface::class);
        $verifier = new ProfileCurrentPasswordVerifier($verifierHasher);
        $service = new ChangeProfilePasswordService($hasher, $verifier);
        $user = $this->user();
        $user->setPassword('old');

        $verifierHasher->expects(self::once())->method('isPasswordValid')->with($user, 'current')->willReturn(true);
        $hasher->expects(self::once())->method('hashPassword')->with($user, 'new-secret')->willReturn('hashed-secret');

        $service->change($user, null, null);
        self::assertSame('old', $user->getPassword());

        try {
            $service->change($user, '   ', 'current');
            self::fail('Expected blank password exception.');
        } catch (InvalidProfilePasswordException $exception) {
            self::assertSame('Le nouveau mot de passe ne peut pas etre vide.', $exception->getMessage());
        }

        $service->change($user, 'new-secret', 'current');
        self::assertSame('hashed-secret', $user->getPassword());
    }

    public function testUserUniqueConstraintViolationDetectorAndPagination(): void
    {
        $driver = new PdoDriverException('Duplicate entry uniq_users_email', null, 0);
        $exception = new UniqueConstraintViolationException($driver, null);
        self::assertTrue(UserUniqueConstraintViolationDetector::isEmail($exception));

        $driver2 = new PdoDriverException('duplicate entry on users.email', null, 0);
        $exception2 = new UniqueConstraintViolationException($driver2, null);
        self::assertTrue(UserUniqueConstraintViolationDetector::isEmail($exception2));

        $driver3 = new PdoDriverException('duplicate entry on users.phone', null, 0);
        $exception3 = new UniqueConstraintViolationException($driver3, null);
        self::assertFalse(UserUniqueConstraintViolationDetector::isEmail($exception3));
        $this->coverPrivateConstructor(UserUniqueConstraintViolationDetector::class);

        $pagination = Pagination::fromRequest(new Request(['page' => -2, 'perPage' => 500]), 20, 100);
        self::assertSame(1, $pagination->page);
        self::assertSame(100, $pagination->perPage);
        self::assertSame(0, $pagination->offset());
        self::assertSame(
            ['page' => 1, 'perPage' => 100, 'total' => 205, 'totalPages' => 3],
            $pagination->metadata(205),
        );
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function coverPrivateConstructor(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($reflection->newInstanceWithoutConstructor());
    }
}
