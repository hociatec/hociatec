<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

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
        if (null !== $type && !in_array($type, ['percent', 'fixed_cents'], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }

        $this->discountType = $type;

        return $this;
    }

    public function getDiscountValue(): ?int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?int $value): self
    {
        if (null !== $value && $value < 0) {
            throw new \InvalidArgumentException('Valeur de remise invalide.');
        }

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
        $now = $now ?? new \DateTimeImmutable();
        $base = $this->getPriceCents();
        if (true !== $this->discountEnabled) {
            return $base;
        }
        if (null !== $this->discountStartsAt && $now < $this->discountStartsAt) {
            return $base;
        }
        if (null !== $this->discountEndsAt && $now > $this->discountEndsAt) {
            return $base;
        }
        if ('percent' === $this->discountType && null !== $this->discountValue) {
            $percent = max(0, min(100, $this->discountValue));
            $discount = (int) round($base * ($percent / 100));

            return max(0, $base - $discount);
        }
        if ('fixed_cents' === $this->discountType && null !== $this->discountValue) {
            return max(0, $base - $this->discountValue);
        }

        return $base;
    }
}
