<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use App\Shared\Mail\DualTransportMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class OrderNotificationEmailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderNotificationContentProvider $contentProvider,
        private readonly DualTransportMailer $mailer,
        private readonly OrderEventLogger $events,
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

        $this->sendScenario($order, 'order_created');
        $order->setOrderCreatedEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
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

        $this->sendScenario($order, 'order_invoice_issued');
        $order->setInvoiceEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
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

        $this->sendScenario($order, $scenarioKey, [
            'previous_order_status' => $oldStatus,
            'previous_order_status_label' => $this->formatStatus($oldStatus),
        ]);

        $sentAt = new \DateTimeImmutable();
        match ($newStatus) {
            Order::STATUS_DELIVERED => $order->setStatusDeliveredEmailSentAt($sentAt),
            Order::STATUS_CANCELLED => $order->setStatusCancelledEmailSentAt($sentAt),
        };

        $this->entityManager->flush();
        $this->events->log($order, null, $force ? 'email_resent' : 'email_sent', ($force ? 'Email client renvoyé: statut ' : 'Email client envoyé: statut ').$this->formatStatus($newStatus).'.');

        return true;
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

        $this->mailer->send(
            $order->getUser()->getEmail(),
            $content['subject'],
            $content['text'],
            $email,
            'order_notification',
        );
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
