<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\Order;

interface OrderPersistencePort
{
    public function commit(): void;

    public function save(Order $order): void;
}
