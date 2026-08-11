<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Port;

use App\Module\Order\Domain\Entity\OrderEvent;

interface OrderEventPersistencePort
{
    public function save(OrderEvent $event): void;

    public function flush(): void;
}
