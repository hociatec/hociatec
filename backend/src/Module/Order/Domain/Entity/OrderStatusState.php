<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderStatusState
{
    #[ORM\Column(name: 'status', length: 20)]
    private string $status = Order::STATUS_PENDING;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
