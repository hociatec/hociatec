<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;

trait OrderStatusEmailNotifierTrait
{
    public function sendStatusChangedIfNeeded(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, false);
    }

    public function resendStatusChanged(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, true);
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
}
