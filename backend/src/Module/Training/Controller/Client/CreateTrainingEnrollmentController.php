<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Client;

use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Entity\TrainingSession;
use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Repository\TrainingSessionRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Module\User\Entity\User;
use App\Module\Order\Service\StripeApiClient;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trainings/enrollments', name: 'api_training_enrollments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateTrainingEnrollmentController extends AbstractController
{
    public function __construct(
        private readonly TrainingSessionRepository $sessions,
        private readonly TrainingEnrollmentRepository $enrollments,
        private readonly TrainingFormatter $formatter,
        private readonly StripeApiClient $stripe,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = (array) json_decode($request->getContent(), true);
        $sessionId = (int) ($payload['sessionId'] ?? 0);
        $session = $sessionId > 0 ? $this->sessions->find($sessionId) : null;
        if ($session === null || !$session->getTraining()->isActive() || $session->getStatus() !== 'scheduled') {
            return ApiResponse::error('Session introuvable.', Response::HTTP_NOT_FOUND);
        }

        $scheduledStartsAtValue = trim((string) ($payload['startsAt'] ?? ''));
        if ($scheduledStartsAtValue === '') {
            return ApiResponse::error('Choisissez une date et une heure de début.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $scheduledStartsAt = new \DateTimeImmutable($scheduledStartsAtValue);
        } catch (\Throwable) {
            return ApiResponse::error('Créneau invalide.', Response::HTTP_BAD_REQUEST);
        }

        $scheduledEndsAt = $scheduledStartsAt->modify('+' . max(1, $session->getTraining()->getDurationMinutes()) . ' minutes');
        $slotError = $this->validateSlot($session, $scheduledStartsAt, $scheduledEndsAt);
        if ($slotError !== null) {
            return ApiResponse::error($slotError, Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $existing = $this->enrollments->findOneForUserAndSession($user, $session);
        if ($existing !== null) {
            if (!in_array($existing->getStatus(), [TrainingEnrollment::STATUS_PENDING_PAYMENT, TrainingEnrollment::STATUS_CANCELLED], true)) {
                return ApiResponse::success($this->formatter->formatEnrollment($existing));
            }

            if ($this->enrollments->countActiveForSessionSlot($session, $scheduledStartsAt, $scheduledEndsAt) >= $session->getCapacity()) {
                return ApiResponse::error('Cette session est complète.', Response::HTTP_BAD_REQUEST);
            }

            $enrollment = $existing;
            $enrollment
                ->setStatus(TrainingEnrollment::STATUS_PENDING_PAYMENT)
                ->setPaidAt(null)
                ->setStripePaymentIntentId(null)
                ->setPriceCents($session->getTraining()->getPriceCents())
                ->setScheduledStartsAt($scheduledStartsAt)
                ->setScheduledEndsAt($scheduledEndsAt);
        } else {
            if ($this->enrollments->countActiveForSessionSlot($session, $scheduledStartsAt, $scheduledEndsAt) >= $session->getCapacity()) {
                return ApiResponse::error('Cette session est complète.', Response::HTTP_BAD_REQUEST);
            }

            $enrollment = new TrainingEnrollment($session, $user, $session->getTraining()->getPriceCents());
            $enrollment
                ->setScheduledStartsAt($scheduledStartsAt)
                ->setScheduledEndsAt($scheduledEndsAt);
            $this->em->persist($enrollment);
            $this->em->flush();
        }

        if ($enrollment->getPriceCents() <= 0) {
            $enrollment
                ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
                ->setPaidAt(null)
                ->setStripeSessionId(null)
                ->setStripePaymentIntentId(null);
            $this->em->flush();

            return ApiResponse::success($this->formatter->formatEnrollment($enrollment), $existing === null ? Response::HTTP_CREATED : Response::HTTP_OK);
        }

        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $stripeSession = $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/trainings/me?payment=success',
            'cancel_url' => $frontendUrl . '/formations/' . $session->getTraining()->getSlug() . '?payment=cancelled',
            'customer_email' => $user->getEmail(),
            'locale' => 'fr',
            'payment_method_types' => ['card'],
            'metadata' => [
                'training_enrollment_id' => (string) $enrollment->getId(),
                'training_session_id' => (string) $session->getId(),
                'user_id' => (string) ($user->getId() ?? 0),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'training_enrollment_id' => (string) $enrollment->getId(),
                    'training_session_id' => (string) $session->getId(),
                    'user_id' => (string) ($user->getId() ?? 0),
                ],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Formation Hociatec - ' . $session->getTraining()->getTitle(),
                        'description' => $session->getFormat() === 'remote' ? 'Session en distanciel' : 'Session en présentiel',
                    ],
                    'unit_amount' => $enrollment->getPriceCents(),
                ],
                'quantity' => 1,
            ]],
        ]);

        $enrollment->setStripeSessionId((string) $stripeSession['id']);
        $this->em->flush();

        $data = $this->formatter->formatEnrollment($enrollment);
        $data['checkoutUrl'] = (string) $stripeSession['url'];

        return ApiResponse::success($data, $existing === null ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    private function validateSlot(TrainingSession $session, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): ?string
    {
        if ($startsAt < $session->getStartsAt() || $endsAt > $session->getEndsAt()) {
            return 'Le créneau doit être compris dans la période de réservation.';
        }

        if ($startsAt->format('Y-m-d') !== $endsAt->format('Y-m-d')) {
            return 'Le créneau doit tenir sur une seule journée.';
        }

        if (!$session->includesWeekends() && in_array((int) $startsAt->format('N'), [6, 7], true)) {
            return 'Cette formation n’est pas réservable le week-end.';
        }

        $slotStart = $startsAt->format('H:i');
        $slotEnd = $endsAt->format('H:i');
        $dailyStart = $session->getDailyStartTime()->format('H:i');
        $dailyEnd = $session->getDailyEndTime()->format('H:i');

        if ($slotStart < $dailyStart || $slotEnd > $dailyEnd) {
            return sprintf('Le créneau doit être compris entre %s et %s.', $dailyStart, $dailyEnd);
        }

        return null;
    }
}
