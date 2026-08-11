<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Auth\Infrastructure\Persistence\RefreshTokenPersistence;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Persistence\OrderPersistence;
use App\Module\Order\Infrastructure\Persistence\StripeWebhookEventPersistence;
use App\Module\Rating\Infrastructure\Persistence\RatingPersistence;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use Doctrine\ORM\EntityManagerInterface;

final class PersistenceHelpersTest extends RepositoryTestCase
{
    public function testSmallPersistenceHelpersDelegateToEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(8))->method('persist');
        $entityManager->expects(self::exactly(7))->method('flush');
        $entityManager->expects(self::exactly(2))->method('remove');

        $user = $this->user();
        $order = new Order('ORD-1', $user);

        (new WorkingDayConfigurationPersistence($entityManager))->save(new WorkingDayConfiguration(1, true));
        (new WorkingDayConfigurationPersistence($entityManager))->commit();
        (new RefreshTokenPersistence($entityManager))->save(new RefreshToken($user, 'selector', 'hashed', new \DateTimeImmutable('+1 day')));
        (new RefreshTokenPersistence($entityManager))->commit();
        (new RatingPersistence($entityManager))->persist(new \stdClass());
        (new RatingPersistence($entityManager))->commit();
        (new TradeInPersistence($entityManager))->save($this->tradeInRequest($user));
        (new TradeInPersistence($entityManager))->remove($this->tradeInRequest($user));
        (new OrderPersistence($entityManager))->commit();
        (new OrderPersistence($entityManager))->save($order);
        (new PrestationPersistence($entityManager))->save(new Prestation('Diag', 30, 1000));
        (new PrestationPersistence($entityManager))->commit();
        (new PrestationPersistence($entityManager))->delete(new Prestation('Diag', 30, 1000));
        (new StripeWebhookEventPersistence($entityManager))->save(new StripeWebhookEvent('evt_1', 'checkout.session.completed', '{}'));
        (new StripeWebhookEventPersistence($entityManager))->commit();
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->persist(new \stdClass());
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->commit();
    }
}
