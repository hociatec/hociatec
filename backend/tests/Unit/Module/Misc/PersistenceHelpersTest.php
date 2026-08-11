<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Persistence\OrderPersistence;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
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
        $unitOfWork = new DoctrineUnitOfWork($entityManager);

        (new WorkingDayConfigurationPersistence($entityManager))->save(new WorkingDayConfiguration(1, true));
        (new WorkingDayConfigurationPersistence($entityManager))->flush();
        $unitOfWork->persist(new RefreshToken($user, 'selector', 'hashed', new \DateTimeImmutable('+1 day')));
        $unitOfWork->flush();
        $unitOfWork->persist(new \stdClass());
        $unitOfWork->flush();
        (new TradeInPersistence($entityManager))->save($this->tradeInRequest($user));
        (new TradeInPersistence($entityManager))->remove($this->tradeInRequest($user));
        (new OrderPersistence($entityManager))->flush();
        (new OrderPersistence($entityManager))->save($order);
        (new PrestationPersistence($entityManager))->save(new Prestation('Diag', 30, 1000));
        (new PrestationPersistence($entityManager))->flush();
        (new PrestationPersistence($entityManager))->delete(new Prestation('Diag', 30, 1000));
        $unitOfWork->persist(new StripeWebhookEvent('evt_1', 'checkout.session.completed', '{}'));
        $unitOfWork->flush();
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->persist(new \stdClass());
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->flush();
    }
}
