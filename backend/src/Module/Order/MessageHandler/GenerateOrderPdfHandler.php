<?php

declare(strict_types=1);

namespace App\Module\Order\MessageHandler;

use App\Module\Order\Message\OrderCreatedMessage;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderInvoiceDocumentService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateOrderPdfHandler
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderInvoiceDocumentService $documents,
        private readonly OrderEventLogger $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderCreatedMessage $message): void
    {
        $order = $this->orders->find($message->orderId);
        if (null === $order) {
            $this->logger->warning('Invoice generation skipped: order not found.', [
                'order_id' => $message->orderId,
                'order_number' => $message->orderNumber,
            ]);

            return;
        }

        try {
            $this->documents->ensureGenerated($order);
            $this->events->log($order, null, 'invoice_generated', 'Facture PDF/XML générée par traitement différé.');
        } catch (\RuntimeException $exception) {
            $this->events->log($order, null, 'invoice_generation_failed', 'Échec génération facture: '.$exception->getMessage());
            throw $exception;
        }

        $this->logger->info('Invoice PDF/XML generated for order.', [
            'order_id' => $message->orderId,
            'order_number' => $message->orderNumber,
            'invoice_number' => $order->getInvoiceNumber(),
        ]);
    }
}
