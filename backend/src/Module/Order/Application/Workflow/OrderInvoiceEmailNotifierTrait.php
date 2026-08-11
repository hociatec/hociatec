<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;

trait OrderInvoiceEmailNotifierTrait
{
    public function sendInvoiceIssuedIfNeeded(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, false);
    }

    public function resendInvoiceIssued(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, true);
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
}
