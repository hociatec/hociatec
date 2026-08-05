<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Workflow;

use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Order\Application\Security\CheckoutRedirectUrlValidator;
use App\Module\Training\Application\DTO\TrainingEnrollmentCheckoutResult;
use App\Module\Training\Application\Exception\TrainingSessionUnavailableException;
use App\Module\Training\Application\Mapper\TrainingSlotValidator;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\Training\Application\Port\TrainingSessionRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class TrainingEnrollmentCheckoutService
{
    public function __construct(
        private TrainingSessionRepositoryPort $sessions,
        private TrainingEnrollmentRepositoryPort $enrollments,
        private TrainingSlotValidator $slots,
        private StripeApiClient $stripe,
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private CheckoutRedirectUrlValidator $redirectUrls,
        private string $frontendUrl,
    ) {
    }

    public function enroll(User $user, int $sessionId, string $startsAt): TrainingEnrollmentCheckoutResult
    {
        return $this->transactions->transactional(fn (): TrainingEnrollmentCheckoutResult => $this->enrollInTransaction($user, $sessionId, $startsAt));
    }

    private function enrollInTransaction(User $user, int $sessionId, string $startsAt): TrainingEnrollmentCheckoutResult
    {
        $session = $this->findSession($sessionId);
        $scheduledStartsAt = $this->parseStart($startsAt);
        $scheduledEndsAt = $scheduledStartsAt->modify('+'.max(1, $session->getTraining()->getDurationMinutes()).' minutes');
        $this->slots->validate($session, $scheduledStartsAt, $scheduledEndsAt);

        $existing = $this->enrollments->findOneForUserAndSession($user, $session);
        if (null !== $existing && !in_array($existing->getStatus(), [
            TrainingEnrollment::STATUS_PENDING_PAYMENT,
            TrainingEnrollment::STATUS_CANCELLED,
        ], true)) {
            return new TrainingEnrollmentCheckoutResult($existing, false);
        }

        $this->assertCapacity($session, $scheduledStartsAt, $scheduledEndsAt);
        $enrollment = $existing ?? new TrainingEnrollment($session, $user, $session->getTraining()->getPriceCents());
        $created = null === $existing;
        $enrollment
            ->setStatus(TrainingEnrollment::STATUS_PENDING_PAYMENT)
            ->setPaidAt(null)
            ->setStripePaymentIntentId(null)
            ->setPriceCents($session->getTraining()->getPriceCents())
            ->setScheduledStartsAt($scheduledStartsAt)
            ->setScheduledEndsAt($scheduledEndsAt);

        if ($created) {
            $this->persistence->persist($enrollment);
        }
        $this->persistence->commit();

        if ($enrollment->getPriceCents() <= 0) {
            $enrollment
                ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
                ->setPaidAt(null)
                ->setStripeSessionId(null)
                ->setStripePaymentIntentId(null);
            $this->persistence->commit();

            return new TrainingEnrollmentCheckoutResult($enrollment, $created);
        }

        $stripeSession = $this->stripe->createCheckoutSession($this->checkoutPayload($enrollment, $user, $session));
        $checkoutUrl = (string) ($stripeSession['url'] ?? '');
        $this->redirectUrls->assertTrusted($checkoutUrl);
        $enrollment->setStripeSessionId((string) $stripeSession['id']);
        $this->persistence->commit();

        return new TrainingEnrollmentCheckoutResult($enrollment, $created, $checkoutUrl);
    }

    private function findSession(int $sessionId): TrainingSession
    {
        $session = $sessionId > 0 ? $this->sessions->findForUpdate($sessionId) : null;
        if (!$session instanceof TrainingSession || !$session->getTraining()->isActive() || 'scheduled' !== $session->getStatus()) {
            throw new TrainingSessionUnavailableException('Session introuvable.');
        }

        return $session;
    }

    private function parseStart(string $value): \DateTimeImmutable
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('Choisissez une date et une heure de début.');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException $exception) {
            throw new \InvalidArgumentException('Créneau invalide.', previous: $exception);
        }
    }

    private function assertCapacity(TrainingSession $session, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): void
    {
        if ($this->enrollments->countActiveForSessionSlot($session, $startsAt, $endsAt) >= $session->getCapacity()) {
            throw new \InvalidArgumentException('Cette session est complète.');
        }
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(TrainingEnrollment $enrollment, User $user, TrainingSession $session): array
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $metadata = [
            'training_enrollment_id' => (string) $enrollment->getId(),
            'training_session_id' => (string) $session->getId(),
            'user_id' => (string) ($user->getId() ?? 0),
        ];

        return [
            'mode' => 'payment',
            'success_url' => $frontendUrl.'/trainings/me?payment=success',
            'cancel_url' => $frontendUrl.'/formations/'.$session->getTraining()->getSlug().'?payment=cancelled',
            'customer_email' => $user->getEmail(),
            'locale' => 'fr',
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Formation Hociatec - '.$session->getTraining()->getTitle(),
                        'description' => 'remote' === $session->getFormat() ? 'Session en distanciel' : 'Session en présentiel',
                    ],
                    'unit_amount' => $enrollment->getPriceCents(),
                ],
                'quantity' => 1,
            ]],
        ];
    }
}
