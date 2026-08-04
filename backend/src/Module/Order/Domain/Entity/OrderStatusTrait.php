<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderStatusTrait
{
    public function getStatus(): string
    {
        return $this->state->getStatus();
    }

    public function setStatus(string $status): self
    {
        $this->state->setStatus($status);

        return $this;
    }
}
