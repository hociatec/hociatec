<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

trait ProductInventoryPublicationTrait
{
    public function getStock(): int
    {
        return $this->inventory->stock();
    }

    public function setStock(int $stock): self
    {
        $this->inventory->changeStock($stock);

        return $this;
    }

    public function increaseStock(int $quantity): self
    {
        $this->inventory->increase($quantity);

        return $this;
    }

    public function decreaseStock(int $quantity): self
    {
        $this->inventory->decrease($quantity);

        return $this;
    }

    public function reserveStock(int $quantity): self
    {
        $this->inventory->reserve($quantity);

        return $this;
    }

    public function releaseStock(int $quantity): self
    {
        $this->inventory->release($quantity);

        return $this;
    }

    public function getLowStockThreshold(): int
    {
        return $this->inventory->lowStockThreshold();
    }

    public function setLowStockThreshold(int $lowStockThreshold): self
    {
        $this->inventory->changeLowStockThreshold($lowStockThreshold);

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->publication->isPublished();
    }

    public function setIsPublished(bool $isPublished): self
    {
        $isPublished ? $this->publication->publish() : $this->publication->unpublish();

        return $this;
    }

    public function publish(): self
    {
        $this->publication->publish();

        return $this;
    }

    public function unpublish(): self
    {
        $this->publication->unpublish();

        return $this;
    }

    public function isFeaturedHome(): bool
    {
        return $this->publication->isFeaturedHome();
    }

    public function setIsFeaturedHome(bool $isFeaturedHome): self
    {
        $isFeaturedHome ? $this->publication->featureOnHomepage() : $this->publication->removeFromHomepage();

        return $this;
    }

    public function featureOnHomepage(): self
    {
        $this->publication->featureOnHomepage();

        return $this;
    }

    public function removeFromHomepage(): self
    {
        $this->publication->removeFromHomepage();

        return $this;
    }
}
