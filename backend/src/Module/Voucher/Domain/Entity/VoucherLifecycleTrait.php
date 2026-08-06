<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\Entity;

use App\Module\Voucher\Domain\ValueObject\VoucherValidityPeriod;

trait VoucherLifecycleTrait
{
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        return $isActive ? $this->activate() : $this->deactivate();
    }

    public function activate(): self
    {
        $this->isActive = true;

        return $this;
    }

    public function deactivate(): self
    {
        $this->isActive = false;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): self
    {
        return $this->scheduleValidity($startsAt, $this->endsAt);
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        return $this->scheduleValidity($this->startsAt, $endsAt);
    }

    public function scheduleValidity(?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): self
    {
        new VoucherValidityPeriod($startsAt, $endsAt);
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function markSent(\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
