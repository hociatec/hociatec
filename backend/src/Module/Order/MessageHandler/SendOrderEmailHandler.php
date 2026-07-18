<?php

declare(strict_types=1);

namespace App\Module\Order\MessageHandler;

use App\Module\Order\Message\OrderCreatedMessage;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderNotificationEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendOrderEmailHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderNotificationEmailService $notifications,
        private readonly OrderEventLogger $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderCreatedMessage $message): void
    {
        $order = $this->orders->find($message->orderId);
        if ($order === null) {
            $this->logger->warning('Order creation email skipped: order not found.', [
                'order_id' => $message->orderId,
                'order_number' => $message->orderNumber,
            ]);

            return;
        }

        try {
            $sent = $this->notifications->sendOrderCreatedIfNeeded($order);
        } catch (\Throwable $exception) {
            $this->events->log($order, null, 'email_failed', 'Échec email commande enregistrée: ' . $exception->getMessage());
            throw $exception;
        }

        $this->logger->info('Order creation email handled.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'sent' => $sent,
        ]);
    }
}
