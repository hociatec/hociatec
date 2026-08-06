<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Notification\Application\Notification\TemplatedEmailFactory;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Order\Application\Port\OrderPersistencePort;
use App\Module\Order\Application\Provider\OrderNotificationContentProvider;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Application\Mail\EmailSender;

final class OrderNotificationEmailService
{
    use OrderCreatedEmailNotifierTrait;
    use OrderInvoiceEmailNotifierTrait;
    use OrderStatusEmailNotifierTrait;

    public function __construct(
        private readonly OrderPersistencePort $persistence,
        private readonly OrderNotificationContentProvider $contentProvider,
        private readonly EmailSender $mailer,
        private readonly OrderEventLogger $events,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly string $mailerFrom,
    ) {
    }

    private function notifyAccount(Order $order, string $scenarioKey, ?string $status = null): void
    {
        $orderId = $order->getId();
        if (null === $orderId) {
            return;
        }

        [$title, $message, $type] = match ($scenarioKey) {
            'order_created' => [
                'Commande enregistrée',
                'Votre commande '.$order->getNumber().' a bien été enregistrée.',
                'order_created',
            ],
            'order_invoice_issued' => [
                'Facture disponible',
                'La facture de votre commande '.$order->getNumber().' est disponible.',
                'order_invoice_issued',
            ],
            'order_status_delivered' => [
                'Commande livrée',
                'Votre commande '.$order->getNumber().' est maintenant livrée.',
                'order_status_delivered',
            ],
            'order_status_cancelled' => [
                'Commande annulée',
                'Votre commande '.$order->getNumber().' est maintenant annulée.',
                'order_status_cancelled',
            ],
            default => [
                'Commande mise à jour',
                'Votre commande '.$order->getNumber().' a été mise à jour.',
                'order_update',
            ],
        };

        $keyStatus = null !== $status ? ':'.$status : '';
        $this->userNotifications->notifyInternal(
            $order->getUser(),
            'order:'.$orderId.':'.$scenarioKey.$keyStatus,
            $title,
            $message,
            '/orders/'.$orderId,
            $type,
        );
    }

    /**
     * @param array<string, string> $extraContext
     */
    private function sendScenario(Order $order, string $scenarioKey, array $extraContext = []): void
    {
        $content = $this->contentProvider->build($order, $scenarioKey, $extraContext);
        $email = TemplatedEmailFactory::create(
            $this->mailerFrom,
            'Hociatec',
            $order->getUser()->getEmail(),
            $order->getUser()->getFullName(),
            $content['subject'],
            $content['html'],
            $content['text'],
        );
        $this->mailer->send($email);
    }

    private function hasStatusNotificationAlreadyBeenSent(Order $order, string $newStatus): bool
    {
        return match ($newStatus) {
            Order::STATUS_DELIVERED => null !== $order->getStatusDeliveredEmailSentAt(),
            Order::STATUS_CANCELLED => null !== $order->getStatusCancelledEmailSentAt(),
            default => true,
        };
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'en attente',
            Order::STATUS_CONFIRMED => 'confirmée',
            Order::STATUS_DELIVERED => 'livrée',
            Order::STATUS_CANCELLED => 'annulée',
            default => $status,
        };
    }
}
