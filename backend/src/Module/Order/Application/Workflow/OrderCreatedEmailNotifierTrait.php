<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;

trait OrderCreatedEmailNotifierTrait
{
    public function sendOrderCreatedIfNeeded(Order $order): bool
    {
        return $this->sendOrderCreated($order, false);
    }

    public function resendOrderCreated(Order $order): bool
    {
        return $this->sendOrderCreated($order, true);
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
        $this->persistence->commit();
        $this->events->log($order, null, 'email_sent', $force ? 'Email client renvoyé: commande enregistrée.' : 'Email client envoyé: commande enregistrée.');

        return true;
    }
}
