<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Entity;

use App\Module\BetaTest\Domain\Enum\BetaCampaignStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'beta_campaigns')]
class BetaCampaign
{
    public const STATUS_DRAFT = BetaCampaignStatus::DRAFT->value;
    public const STATUS_ACTIVE = BetaCampaignStatus::ACTIVE->value;
    public const STATUS_CLOSED = BetaCampaignStatus::CLOSED->value;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 120)] private string $name;
    #[ORM\Column(type: 'text')] private string $description;
    #[ORM\Column(length: 30, enumType: BetaCampaignStatus::class)] private BetaCampaignStatus $status = BetaCampaignStatus::DRAFT;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $startsAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $endsAt;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $createdAt;
    public function __construct(string $name, string $description, ?\DateTimeImmutable $startsAt = null, ?\DateTimeImmutable $endsAt = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getEffectiveStatus(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();

        if (BetaCampaignStatus::ACTIVE === $this->status && null !== $this->endsAt && $this->endsAt < $now) {
            return self::STATUS_CLOSED;
        }

        return $this->status->value;
    }

    public function isOpenForReports(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return BetaCampaignStatus::ACTIVE === $this->status
            && (null === $this->startsAt || $this->startsAt <= $now)
            && (null === $this->endsAt || $this->endsAt >= $now);
    }

    public function setStatus(BetaCampaignStatus|string $status): self
    {
        if (!$status instanceof BetaCampaignStatus) {
            $status = BetaCampaignStatus::tryFrom($status);
            if (null === $status) {
                throw new \InvalidArgumentException('État de campagne invalide.');
            }
        }

        $this->status = $status;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }
}
