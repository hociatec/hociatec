<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\Order\Domain\Entity\Order;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class OrderNotificationEmailService
{
    public function __construct(
        private readonly OrderPersistence $persistence,
        private readonly OrderNotificationContentProvider $contentProvider,
        private readonly MailerInterface $mailer,
        private readonly OrderEventLogger $events,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly string $mailerFrom,
    ) {
    }

    public function sendOrderCreatedIfNeeded(Order $order): bool
    {
        return $this->sendOrderCreated($order, false);
    }

    public function sendInvoiceIssuedIfNeeded(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, false);
    }

    public function sendStatusChangedIfNeeded(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, false);
    }

    public function resendOrderCreated(Order $order): bool
    {
        return $this->sendOrderCreated($order, true);
    }

    public function resendInvoiceIssued(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, true);
    }

    public function resendStatusChanged(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, true);
    }

    private function sendOrderCreated(Order $order, bool $force): bool
    {
        if (!$force && null !== $order->getOrderCreatedEmailSentAt()) {
            return false;
        }

        $this->notifyAccount($order, 'order_created');
        if (!$this->userNotifications->shouldSendEmail($order->getUser())) {
            return false;
        }

        $this->sendScenario($order, 'order_created');
        $order->setOrderCreatedEmailSentAt(new \DateTimeImmutable());
        $this->persistence->flush();
        $this->events->log($order, null, 'email_sent', $force ? 'Email client renvoyé: commande enregistrée.' : 'Email client envoyé: commande enregistrée.');

        return true;
    }

    private function sendInvoiceIssued(Order $order, bool $force): bool
    {
        if (
            (!$force && null !== $order->getInvoiceEmailSentAt())
            || null === $order->getInvoicePdfPath()
            || null === $order->getInvoiceXmlPath()
        ) {
            return false;
        }

        $this->notifyAccount($order, 'order_invoice_issued');
        if (!$this->userNotifications->shouldSendEmail($order->getUser())) {
            return false;
        }

        $this->sendScenario($order, 'order_invoice_issued');
        $order->setInvoiceEmailSentAt(new \DateTimeImmutable());
        $this->persistence->flush();
        $this->events->log($order, null, $force ? 'email_resent' : 'email_sent', $force ? 'Email client renvoyé: facture disponible.' : 'Email client envoyé: facture disponible.');

        return true;
    }

    private function sendStatusChanged(Order $order, string $oldStatus, string $newStatus, bool $force): bool
    {
        $scenarioKey = match ($newStatus) {
            Order::STATUS_DELIVERED => 'order_status_delivered',
            Order::STATUS_CANCELLED => 'order_status_cancelled',
            default => null,
        };

        if (null === $scenarioKey || (!$force && $this->hasStatusNotificationAlreadyBeenSent($order, $newStatus))) {
            return false;
        }

        $this->notifyAccount($order, $scenarioKey, $newStatus);
        if (!$this->userNotifications->shouldSendEmail($order->getUser())) {
            return false;
        }

        $this->sendScenario($order, $scenarioKey, [
            'previous_order_status' => $oldStatus,
            'previous_order_status_label' => $this->formatStatus($oldStatus),
        ]);

        $sentAt = new \DateTimeImmutable();
        match ($newStatus) {
            Order::STATUS_DELIVERED => $order->setStatusDeliveredEmailSentAt($sentAt),
            Order::STATUS_CANCELLED => $order->setStatusCancelledEmailSentAt($sentAt),
        };

        $this->persistence->flush();
        $this->events->log($order, null, $force ? 'email_resent' : 'email_sent', ($force ? 'Email client renvoyé: statut ' : 'Email client envoyé: statut ').$this->formatStatus($newStatus).'.');

        return true;
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
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($order->getUser()->getEmail(), $order->getUser()->getFullName()))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

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
