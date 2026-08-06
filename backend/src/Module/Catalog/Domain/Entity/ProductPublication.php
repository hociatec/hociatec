<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductPublication
{
    #[ORM\Column(type: 'boolean')]
    private bool $isPublished = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isFeaturedHome = false;

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function changePublished(bool $published): void
    {
        $this->isPublished = $published;
    }

    public function publish(): void
    {
        $this->isPublished = true;
    }

    public function unpublish(): void
    {
        $this->isPublished = false;
    }

    public function isFeaturedHome(): bool
    {
        return $this->isFeaturedHome;
    }

    public function changeFeaturedHome(bool $featuredHome): void
    {
        $this->isFeaturedHome = $featuredHome;
    }

    public function featureOnHomepage(): void
    {
        $this->isFeaturedHome = true;
    }

    public function removeFromHomepage(): void
    {
        $this->isFeaturedHome = false;
    }
}
