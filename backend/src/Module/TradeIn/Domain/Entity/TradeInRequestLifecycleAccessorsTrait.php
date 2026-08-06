<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;

trait TradeInRequestLifecycleAccessorsTrait
{
    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function getStatus(): TradeInStatus
    {
        return $this->status;
    }

    public function getConsentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setStatus(TradeInStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function setOffer(?int $offerCents, ?\DateTimeImmutable $expiresAt = null): self
    {
        if (null !== $offerCents && $offerCents < 0) {
            throw new \InvalidArgumentException('Le montant de l’offre ne peut pas être négatif.');
        }

        $this->offerCents = $offerCents;
        $this->offerExpiresAt = $expiresAt;
        $this->touch();

        return $this;
    }

    public function setAdminNote(?string $note): self
    {
        $this->adminNote = null !== $note ? trim($note) : null;
        $this->touch();

        return $this;
    }
}
