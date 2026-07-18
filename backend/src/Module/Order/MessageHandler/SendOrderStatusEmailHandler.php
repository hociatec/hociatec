<?php

declare(strict_types=1);

namespace App\Module\Order\MessageHandler;

use App\Module\Order\Message\OrderStatusChangedMessage;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderNotificationEmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendOrderStatusEmailHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderNotificationEmailService $notifications,
        private readonly OrderEventLogger $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderStatusChangedMessage $message): void
    {
        $order = $this->orders->find($message->orderId);
        if ($order === null) {
            $this->logger->warning('Order status email skipped: order not found.', [
                'order_id' => $message->orderId,
                'order_number' => $message->orderNumber,
                'new_status' => $message->newStatus,
            ]);

            return;
        }

        try {
            $sent = $this->notifications->sendStatusChangedIfNeeded($order, $message->oldStatus, $message->newStatus);
        } catch (\Throwable $exception) {
            $this->events->log($order, null, 'email_failed', 'Échec email statut ' . $message->newStatus . ': ' . $exception->getMessage());
            throw $exception;
        }

        $this->logger->info('Order status email handled.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'old_status' => $message->oldStatus,
            'new_status' => $message->newStatus,
            'sent' => $sent,
        ]);
    }
}
