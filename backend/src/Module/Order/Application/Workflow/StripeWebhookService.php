<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Handler\OrderStripeWebhookHandler;
use App\Module\Order\Application\Handler\RefundStripeWebhookHandler;
use App\Module\Order\Application\Handler\TrainingStripeWebhookHandler;
use App\Module\Order\Application\Mapper\StripeWebhookVerifier;
use App\Module\Order\Application\Persistence\StripeWebhookEventPersistence;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Repository\StripeWebhookEventRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class StripeWebhookService
{
    public function __construct(
        private readonly StripeWebhookVerifier $verifier,
        private readonly OrderStripeWebhookHandler $orders,
        private readonly TrainingStripeWebhookHandler $training,
        private readonly RefundStripeWebhookHandler $refunds,
        private readonly StripeWebhookEventRepository $events,
        private readonly StripeWebhookEventPersistence $persistence,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(string $payload, ?string $signatureHeader): array
    {
        $event = $this->verifier->verifyAndDecode($payload, $signatureHeader);
        $eventId = is_string($event['id'] ?? null) ? $event['id'] : null;
        $type = (string) ($event['type'] ?? '');
        if (null === $eventId || '' === $type) {
            throw new \InvalidArgumentException('Webhook Stripe invalide.');
        }

        $received = $this->events->findOneByStripeEventId($eventId);
        if ($received?->isProcessed()) {
            return ['type' => $type, 'eventId' => $eventId, 'duplicate' => true];
        }
        if (null === $received) {
            $received = new StripeWebhookEvent($eventId, $type);
            try {
                $this->persistence->save($received);
                $this->persistence->commit();
            } catch (UniqueConstraintViolationException) {
                $received = $this->events->findOneByStripeEventId($eventId);
                if ($received?->isProcessed()) {
                    return ['type' => $type, 'eventId' => $eventId, 'duplicate' => true];
                }
            }
        }

        $object = $event['data']['object'] ?? null;

        if (!is_array($object)) {
            throw new \RuntimeException('Webhook Stripe invalide.');
        }

        try {
            $result = in_array($type, [
                'checkout.session.completed',
                'checkout.session.async_payment_succeeded',
                'checkout.session.expired',
                'checkout.session.async_payment_failed',
            ], true) ? $this->handleCheckout($object, $type) : match ($type) {
                'payment_intent.payment_failed' => $this->orders->handlePaymentIntentFailed($object, $type),
                'refund.created', 'refund.updated', 'refund.failed' => $this->refunds->handle($object, $type),
                default => [
                    'type' => $type,
                    'sessionId' => is_string($object['id'] ?? null) ? $object['id'] : null,
                ],
            };
            $received?->markProcessed();
            $this->persistence->commit();

            return ['eventId' => $eventId] + $result;
        } catch (\RuntimeException $exception) {
            $received?->markFailed($exception->getMessage());
            $this->persistence->commit();
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array<string, mixed>
     */
    private function handleCheckout(array $object, string $type): array
    {
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if (null === $sessionId) {
            throw new \RuntimeException('Session Stripe introuvable.');
        }

        return $this->orders->handleCheckout($object, $type)
            ?? $this->training->handleCheckout($object, $type)
            ?? ['type' => $type, 'sessionId' => $sessionId];
    }
}
