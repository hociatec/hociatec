<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Auth\Infrastructure\Security\UserChecker;
use App\Module\BetaTest\Application\DTO\BetaProfileInput;
use App\Module\BetaTest\Application\Workflow\BetaTesterProfileService;
use App\Module\Order\Application\Message\OrderStatusChangedMessage;
use App\Module\Order\Infrastructure\MessageHandler\SyncOrderExternalHandler;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

final class SmallServiceTailBatchTest extends TestCase
{
    public function testUserCheckerHandlesVerifiedPendingAndForeignUsers(): void
    {
        $checker = new UserChecker();

        $verified = $this->user();
        $verified->setIsVerified(true);
        $checker->checkPreAuth($verified);
        $checker->checkPostAuth($verified);

        $foreignUser = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'x';
            }
        };
        $checker->checkPreAuth($foreignUser);

        try {
            $checker->checkPreAuth($this->user());
            self::fail('Expected inactive account exception.');
        } catch (CustomUserMessageAccountStatusException $exception) {
            self::assertSame('Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.', $exception->getMessage());
        }
    }

    public function testBetaProfileServiceAndSyncHandler(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $persistence = new DoctrineUnitOfWork($entityManager);

        $user = $this->user();
        $profileService = new BetaTesterProfileService($persistence);
        $profile = $profileService->create($user, new BetaProfileInput(
            ['weekly'],
            'motivation',
            'advanced',
            'high',
            'expert',
            'none',
            ['screen-reader'],
            ['mac'],
            ['chrome'],
            ['ui'],
            true,
        ));
        self::assertSame($user, $profile->getUser());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')->with('Queued order status sync.', self::arrayHasKey('order_number'));
        (new SyncOrderExternalHandler($logger))->__invoke(new OrderStatusChangedMessage(9, 'ORD-9', 'pending', 'confirmed'));
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }
}
