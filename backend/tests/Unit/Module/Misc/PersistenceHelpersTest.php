<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Auth\Domain\Entity\RefreshToken;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
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

        $unitOfWork->persist(new WorkingDayConfiguration(1, true));
        $unitOfWork->flush();
        $unitOfWork->persist(new RefreshToken($user, 'selector', 'hashed', new \DateTimeImmutable('+1 day')));
        $unitOfWork->flush();
        $unitOfWork->persist(new \stdClass());
        $unitOfWork->flush();
        $unitOfWork->persist($this->tradeInRequest($user));
        $unitOfWork->remove($this->tradeInRequest($user));
        $unitOfWork->flush();
        $unitOfWork->persist($order);
        $prestation = new Prestation('Diag', 30, 1000);
        $unitOfWork->persist($prestation);
        $unitOfWork->flush();
        $unitOfWork->remove($prestation);
        $unitOfWork->persist(new StripeWebhookEvent('evt_1', 'checkout.session.completed', '{}'));
        $unitOfWork->flush();
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->persist(new \stdClass());
        (new \App\Module\Admin\Infrastructure\Operations\Persistence\DoctrineOperationsPersistence($entityManager))->flush();
    }
}
