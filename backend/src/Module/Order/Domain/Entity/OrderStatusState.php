<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\Enum\OrderStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderStatusState
{
    #[ORM\Column(name: 'status', length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::PENDING;

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function setStatus(string $status): self
    {
        $this->status = OrderStatus::from($status);

        return $this;
    }
}
