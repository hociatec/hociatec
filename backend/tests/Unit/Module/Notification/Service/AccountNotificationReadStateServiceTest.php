<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Notification\Application\Service\AccountNotificationReadStateService;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Service\UserPersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AccountNotificationReadStateServiceTest extends TestCase
{
    public function testReadHandlesJsonScalarAndInvalidPayloads(): void
    {
        $service = new AccountNotificationReadStateService(new UserPersistence($this->createMock(EntityManagerInterface::class)));
        $user = $this->user();

        self::assertSame(['seenKeys' => [], 'dismissedKeys' => [], 'seenSignature' => ''], $service->read($user));

        $user->setAccountNotificationsSeenSignature('"scalar"');
        self::assertSame(['seenKeys' => [], 'dismissedKeys' => [], 'seenSignature' => ''], $service->read($user));

        $user->setAccountNotificationsSeenSignature(json_encode([
            'seenKeys' => [' a ', 'a', '', str_repeat('x', 256), 12],
            'dismissedKeys' => [' b ', '', 'b'],
        ], JSON_THROW_ON_ERROR));
        self::assertSame(
            ['seenKeys' => ['a'], 'dismissedKeys' => ['b'], 'seenSignature' => 'a'],
            $service->read($user),
        );

        $user->setAccountNotificationsSeenSignature(json_encode([
            'seenKeys' => 'not-an-array',
            'dismissedKeys' => 'still-not-an-array',
        ], JSON_THROW_ON_ERROR));
        self::assertSame(
            ['seenKeys' => [], 'dismissedKeys' => [], 'seenSignature' => ''],
            $service->read($user),
        );
    }

    public function testUpdateMergesSignatureOnlyWhenNoExplicitKeysAreProvided(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('flush');

        $service = new AccountNotificationReadStateService(new UserPersistence($entityManager));
        $user = $this->user();
        $user->setAccountNotificationsSeenSignature(json_encode([
            'seenKeys' => ['seen-1'],
            'dismissedKeys' => ['dismissed-1'],
        ], JSON_THROW_ON_ERROR));

        $updated = $service->update($user, new NotificationReadStateInput(null, null, null, " seen-2 \n\n seen-3 \n seen-2 "));
        self::assertSame(['seen-1', 'seen-2', 'seen-3'], $updated['seenKeys']);
        self::assertSame(['dismissed-1'], $updated['dismissedKeys']);
        self::assertSame("seen-1\nseen-2\nseen-3", $updated['seenSignature']);

        $updated = $service->update($user, new NotificationReadStateInput(['seen-4'], null, null, "ignored\nsignature"));
        self::assertSame(['seen-1', 'seen-2', 'seen-3', 'seen-4'], $updated['seenKeys']);
        self::assertSame(['dismissed-1'], $updated['dismissedKeys']);

        $updated = $service->update($user, new NotificationReadStateInput(null, ' dismissed-2 ', ['dismissed-3', ' dismissed-2 '], null));
        self::assertSame(['seen-1', 'seen-2', 'seen-3', 'seen-4', 'dismissed-2', 'dismissed-3'], $updated['seenKeys']);
        self::assertSame(['dismissed-1', 'dismissed-2', 'dismissed-3'], $updated['dismissedKeys']);
    }

    public function testReadFallsBackToLegacyNewlineSignatureAndUpdateIgnoresBlankDismissals(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new AccountNotificationReadStateService(new UserPersistence($entityManager));
        $user = $this->user();
        $user->setAccountNotificationsSeenSignature(" alpha \n\n beta \n alpha \n");

        self::assertSame(
            ['seenKeys' => ['alpha', 'beta'], 'dismissedKeys' => [], 'seenSignature' => "alpha\nbeta"],
            $service->read($user),
        );

        $updated = $service->update($user, new NotificationReadStateInput(null, '   ', [' beta ', '', 'gamma'], null));
        self::assertSame(['alpha', 'beta', 'gamma'], $updated['seenKeys']);
        self::assertSame(['beta', 'gamma'], $updated['dismissedKeys']);
        self::assertStringContainsString('"seenKeys":["alpha","beta","gamma"]', $user->getAccountNotificationsSeenSignature() ?? '');
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }
}
