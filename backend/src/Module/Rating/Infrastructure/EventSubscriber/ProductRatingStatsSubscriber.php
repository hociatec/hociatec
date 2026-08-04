<?php

declare(strict_types=1);

namespace App\Module\Rating\Infrastructure\EventSubscriber;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Rating\Application\Writer\ProductReviewStatsUpdater;
use App\Module\Rating\Domain\Entity\ProductRating;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Ensures cached review statistics on Product stay in sync when ratings change.
 */
final class ProductRatingStatsSubscriber implements EventSubscriber
{
    /**
     * @var array<string, Product>
     */
    private array $productsToRefresh = [];

    public function __construct(
        private readonly ProductReviewStatsUpdater $statsUpdater,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProductRating) {
            return;
        }

        $this->scheduleProduct($entity->getProduct());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProductRating) {
            return;
        }

        $this->scheduleProduct($entity->getProduct());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProductRating) {
            return;
        }

        $this->scheduleProduct($entity->getProduct());
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->productsToRefresh) {
            return;
        }

        $products = $this->productsToRefresh;
        $this->productsToRefresh = [];

        foreach ($products as $product) {
            $this->statsUpdater->refresh($product);
        }
    }

    private function scheduleProduct(Product $product): void
    {
        $id = $product->getId();
        if (null === $id) {
            $this->productsToRefresh[\sprintf('tmp_%s', \spl_object_id($product))] = $product;

            return;
        }

        $this->productsToRefresh['id_'.$id] = $product;
    }
}
