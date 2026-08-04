<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\MessageHandler;

use App\Module\Order\Application\Message\OrderStatusChangedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncOrderExternalHandler
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(OrderStatusChangedMessage $message): void
    {
        // Placeholder: sync status with external system
        $this->logger->info('Queued order status sync.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'old' => $message->oldStatus,
            'new' => $message->newStatus,
        ]);
    }
}
