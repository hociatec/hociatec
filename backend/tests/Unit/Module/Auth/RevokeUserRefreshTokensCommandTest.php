<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth;

use App\Module\Auth\Infrastructure\Command\RevokeUserRefreshTokensCommand;
use App\Module\Auth\Infrastructure\Command\RevokeAllRefreshTokensCommand;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RevokeUserRefreshTokensCommandTest extends AuthIntegrationTestCase
{
    public function testItRevokesAllRefreshTokensForTheRequestedUser(): void
    {
        $em = $this->entityManager();
        $user = $this->user('revoke-me@example.test');
        $otherUser = $this->user('other@example.test');
        $em->persist($user);
        $em->persist($otherUser);
        $em->flush();

        $service = $this->refreshService($em);
        $targetToken = $service->issueForUser($user)['refreshToken'];
        $otherToken = $service->issueForUser($otherUser)['refreshToken'];

        $tester = new CommandTester(new RevokeUserRefreshTokensCommand(
            $this->userRepository($em),
            new \App\Module\Auth\Application\Workflow\RefreshTokenRevocationService($this->refreshRepository($em)),
            new DoctrineUnitOfWork($em),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => 'revoke-me@example.test']));
        self::assertStringContainsString('revoke-me@example.test', $tester->getDisplay());
        self::assertStringContainsString('révoquées', $tester->getDisplay());
        self::assertNull($service->rotate($targetToken));
        self::assertNotNull($service->rotate($otherToken));
    }

    public function testItFailsForUnknownOrBlankEmail(): void
    {
        $em = $this->entityManager();
        $tester = new CommandTester(new RevokeUserRefreshTokensCommand(
            $this->userRepository($em),
            new \App\Module\Auth\Application\Workflow\RefreshTokenRevocationService($this->refreshRepository($em)),
            new DoctrineUnitOfWork($em),
        ));

        self::assertSame(Command::INVALID, $tester->execute(['email' => '   ']));
        self::assertStringContainsString('Une adresse e-mail valide est requise.', $tester->getDisplay());

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'missing@example.test']));
        self::assertStringContainsString('Utilisateur introuvable.', $tester->getDisplay());
    }

    public function testItCanRevokeAllActiveRefreshTokensGlobally(): void
    {
        $em = $this->entityManager();
        $userA = $this->user('all-a@example.test');
        $userB = $this->user('all-b@example.test');
        $em->persist($userA);
        $em->persist($userB);
        $em->flush();

        $service = $this->refreshService($em);
        $tokenA = $service->issueForUser($userA)['refreshToken'];
        $tokenB = $service->issueForUser($userB)['refreshToken'];

        $tester = new CommandTester(new RevokeAllRefreshTokensCommand(
            new \App\Module\Auth\Application\Workflow\RefreshTokenRevocationService($this->refreshRepository($em)),
            new DoctrineUnitOfWork($em),
        ));

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('--confirm', $tester->getDisplay());

        self::assertSame(Command::SUCCESS, $tester->execute(['--confirm' => true]));
        self::assertStringContainsString('révoquées globalement', $tester->getDisplay());
        self::assertNull($service->rotate($tokenA));
        self::assertNull($service->rotate($tokenB));
    }
}
