<?php

declare(strict_types=1);

namespace App\Module\Order\MessageHandler;

use App\Module\Order\Message\OrderCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendOrderEmailHandler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function __invoke(OrderCreatedMessage $message): void
    {
        // Placeholder: implement emailing via symfony/mailer
        $this->logger->info('Queued email for order creation.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'user_id' => $message->userId,
        ]);
    }
}

