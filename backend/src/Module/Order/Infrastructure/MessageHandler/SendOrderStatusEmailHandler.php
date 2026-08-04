<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\MessageHandler;

use App\Module\Order\Application\Message\OrderStatusChangedMessage;
use App\Module\Order\Application\Service\OrderEventLogger;
use App\Module\Order\Application\Service\OrderNotificationEmailService;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
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
        if (null === $order) {
            $this->logger->warning('Order status email skipped: order not found.', [
                'order_id' => $message->orderId,
                'order_number' => $message->orderNumber,
                'new_status' => $message->newStatus,
            ]);

            return;
        }

        try {
            $sent = $this->notifications->sendStatusChangedIfNeeded($order, $message->oldStatus, $message->newStatus);
        } catch (\RuntimeException $exception) {
            $this->events->log($order, null, 'email_failed', 'Échec email statut '.$message->newStatus.': '.$exception->getMessage());
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
