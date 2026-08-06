<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Message\OrderCreatedMessage;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use Psr\Log\LoggerInterface;

final readonly class OrderPostCreationProcessor
{
    public function __construct(
        private OrderInvoiceDocumentService $invoiceDocuments,
        private OrderNotificationEmailService $notifications,
        private OrderEventLogger $events,
        private AsyncMessageDispatcher $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function process(Order $order, User $user, bool $afterStripePayment): void
    {
        if ($afterStripePayment) {
            $this->events->log($order, $user, 'payment_confirmed', 'Paiement Stripe confirmé.');
            $createdMessage = 'Commande créée après paiement Stripe.';
        } else {
            $createdMessage = 'Commande créée et confirmée automatiquement.';
        }

        $this->events->log($order, $user, 'order_created', $createdMessage);
        $this->bus->dispatch(new OrderCreatedMessage(
            $order->getId() ?? 0,
            $order->getNumber(),
            $user->getId() ?? 0,
        ));

        try {
            $this->invoiceDocuments->ensureGenerated($order);
            $this->events->log($order, $user, 'invoice_generated', 'Facture PDF/XML générée.');
            $this->notifications->sendOrderCreatedIfNeeded($order);
        } catch (\RuntimeException $exception) {
            $context = $afterStripePayment ? 'post-paiement' : 'post-commande';
            $this->events->log(
                $order,
                $user,
                'post_processing_failed',
                sprintf('Échec %s: %s', $context, $exception->getMessage()),
            );
            $this->logger->error('Order post-processing failed.', [
                'context' => $context,
                'order_id' => $order->getId(),
                'order_number' => $order->getNumber(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
