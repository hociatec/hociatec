<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use App\Module\Catalog\Domain\ValueObject\ProductDiscount;
use Doctrine\ORM\Mapping as ORM;

trait ProductDiscountTrait
{
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $discountType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $discountValue = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $discountStartsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $discountEndsAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $discountEnabled = false;

    public function isDiscountEnabled(): bool
    {
        return $this->discountEnabled;
    }

    public function setDiscountEnabled(bool $enabled): self
    {
        $this->discountEnabled = $enabled;

        return $this;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(?string $type): self
    {
        new ProductDiscount(false, $type, null, $this->discountStartsAt, $this->discountEndsAt);
        $this->discountType = $type;

        return $this;
    }

    public function getDiscountValue(): ?int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?int $value): self
    {
        new ProductDiscount($this->discountEnabled, $this->discountType, $value, $this->discountStartsAt, $this->discountEndsAt);
        $this->discountValue = $value;

        return $this;
    }

    public function getDiscountStartsAt(): ?\DateTimeImmutable
    {
        return $this->discountStartsAt;
    }

    public function setDiscountStartsAt(?\DateTimeImmutable $date): self
    {
        $this->discountStartsAt = $date;

        return $this;
    }

    public function getDiscountEndsAt(): ?\DateTimeImmutable
    {
        return $this->discountEndsAt;
    }

    public function setDiscountEndsAt(?\DateTimeImmutable $date): self
    {
        $this->discountEndsAt = $date;

        return $this;
    }

    public function getEffectivePriceCents(?\DateTimeImmutable $now = null): int
    {
        return $this->getDisplayEffectivePriceCents(null, $now);
    }

    public function discount(): ProductDiscount
    {
        return new ProductDiscount($this->discountEnabled, $this->discountType, $this->discountValue, $this->discountStartsAt, $this->discountEndsAt);
    }
}
