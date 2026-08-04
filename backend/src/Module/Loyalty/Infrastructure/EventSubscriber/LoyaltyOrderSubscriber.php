<?php

declare(strict_types=1);

namespace App\Module\Loyalty\Infrastructure\EventSubscriber;

use App\Module\Loyalty\Application\Workflow\LoyaltyService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

final class LoyaltyOrderSubscriber implements EventSubscriber
{
    public function __construct(private readonly LoyaltyService $loyalty)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $orderMetadata = $em->getClassMetadata(Order::class);
        $userMetadata = $em->getClassMetadata(User::class);

        $orders = [
            ...$uow->getScheduledEntityInsertions(),
            ...$uow->getScheduledEntityUpdates(),
        ];

        foreach ($orders as $entity) {
            if (!$entity instanceof Order) {
                continue;
            }

            $previousOrderPoints = $entity->getLoyaltyPointsAwarded();
            $previousUserPoints = $entity->getUser()->getLoyaltyPointsBalance();

            $this->loyalty->syncOrderPoints($entity);

            if ($entity->getLoyaltyPointsAwarded() !== $previousOrderPoints) {
                $uow->recomputeSingleEntityChangeSet($orderMetadata, $entity);
            }

            if ($entity->getUser()->getLoyaltyPointsBalance() !== $previousUserPoints) {
                $em->persist($entity->getUser());
                $uow->recomputeSingleEntityChangeSet($userMetadata, $entity->getUser());
            }
        }
    }
}
