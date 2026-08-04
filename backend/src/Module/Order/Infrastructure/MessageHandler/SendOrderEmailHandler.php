<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\MessageHandler;

use App\Module\Order\Application\Message\OrderCreatedMessage;
use App\Module\Order\Application\Service\OrderEventLogger;
use App\Module\Order\Application\Service\OrderNotificationEmailService;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
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
        if (null === $order) {
            $this->logger->warning('Order creation email skipped: order not found.', [
                'order_id' => $message->orderId,
                'order_number' => $message->orderNumber,
            ]);

            return;
        }

        try {
            $sent = $this->notifications->sendOrderCreatedIfNeeded($order);
        } catch (\RuntimeException $exception) {
            $this->events->log($order, null, 'email_failed', 'Échec email commande enregistrée: '.$exception->getMessage());
            throw $exception;
        }

        $this->logger->info('Order creation email handled.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'sent' => $sent,
        ]);
    }
}
