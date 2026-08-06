<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Application\Mapper\ProfileCurrentPasswordVerifier;
use App\Module\User\Application\Workflow\ChangeProfileEmailService;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use App\Module\User\Application\Port\UserPasswordHasher;

final class ContactAndEmailChangeServicesTest extends TestCase
{
    public function testChangeProfileEmailServiceHandlesNoChangeDuplicateAndSuccess(): void
    {
        $user = $this->user();
        $user->setEmail('ada@example.com');

        $repository = $this->createMock(UserRepository::class);
        $hasher = $this->createMock(UserPasswordHasher::class);
        $hasher->expects(self::exactly(2))->method('isPasswordValid')->with($user, 'secret')->willReturn(true);
        $service = new ChangeProfileEmailService($repository, new ProfileCurrentPasswordVerifier($hasher));

        $service->change($user, 9, 'ADA@example.com', 'secret');
        self::assertSame('ada@example.com', $user->getEmail());

        $repository->expects(self::exactly(2))->method('existsByEmailExcludingUser')->with('new@example.com', 9)->willReturnOnConsecutiveCalls(true, false);

        try {
            $service->change($user, 9, 'new@example.com', 'secret');
            self::fail('Expected duplicate email exception.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertSame('Cet email est deja utilise par un autre compte.', $exception->getMessage());
        }

        $service->change($user, 9, 'new@example.com', 'secret');
        self::assertSame('new@example.com', $user->getEmail());
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }
}
