<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

trait QuoteLifecycleTrait
{
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function markDraft(): self
    {
        return $this->setStatus(self::STATUS_DRAFT);
    }

    public function markSent(): self
    {
        return $this->setStatus(self::STATUS_SENT);
    }

    public function accept(): self
    {
        return $this->setStatus(self::STATUS_ACCEPTED);
    }

    public function refuse(): self
    {
        return $this->setStatus(self::STATUS_REFUSED);
    }

    public function expire(): self
    {
        return $this->setStatus(self::STATUS_EXPIRED);
    }
}
