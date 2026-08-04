<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;

final class TrainingStripeWebhookHandler
{
    public function __construct(
        private readonly TrainingEnrollmentRepository $enrollments,
        private readonly DoctrineUnitOfWork $persistence,
    ) {
    }

    /**
     * @param array<string, mixed> $object
     *
     * @return array{type:string, sessionId:string|null}|null
     */
    public function handleCheckout(array $object, string $type): ?array
    {
        $sessionId = is_string($object['id'] ?? null) ? $object['id'] : null;
        if (null === $sessionId) {
            return null;
        }

        $enrollment = $this->enrollments->findOneByStripeSessionId($sessionId);
        if (!$enrollment instanceof TrainingEnrollment) {
            return null;
        }

        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if (($object['payment_status'] ?? null) === 'paid') {
                $enrollment
                    ->setStatus(TrainingEnrollment::STATUS_PAID)
                    ->setPaidAt(new \DateTimeImmutable())
                    ->setStripePaymentIntentId(
                        is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null,
                    );
            }
        } elseif (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $enrollment->setStatus(TrainingEnrollment::STATUS_CANCELLED);
        }

        $this->persistence->flush();

        return ['type' => $type, 'sessionId' => $enrollment->getStripeSessionId()];
    }
}
