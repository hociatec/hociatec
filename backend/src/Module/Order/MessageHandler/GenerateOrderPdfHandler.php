<?php

declare(strict_types=1);

namespace App\Module\Order\MessageHandler;

use App\Module\Order\Message\OrderCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateOrderPdfHandler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function __invoke(OrderCreatedMessage $message): void
    {
        // Placeholder: generate and store invoice/summary PDF
        $this->logger->info('Queued PDF generation for order.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
        ]);
    }
}

