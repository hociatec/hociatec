<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Entity\TrainingSession;
use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Repository\TrainingSessionRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-sessions/{id}', name: 'api_admin_training_sessions_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[Route('/api/admin/training-sessions', name: 'api_admin_training_sessions_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class SaveTrainingSessionController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepository $trainings,
        private readonly TrainingSessionRepository $sessions,
        private readonly TrainingFormatter $formatter,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        $payload = (array) json_decode($request->getContent(), true);
        $training = $this->trainings->find((int) ($payload['trainingId'] ?? 0));
        if ($training === null) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $startsAt = new DateTimeImmutable((string) ($payload['startsAt'] ?? 'now'));
        $endsAt = new DateTimeImmutable((string) ($payload['endsAt'] ?? $startsAt->modify('+1 day')->format(DateTimeImmutable::ATOM)));
        $dailyStartTime = new DateTimeImmutable((string) ($payload['dailyStartTime'] ?? '08:00'));
        $dailyEndTime = new DateTimeImmutable((string) ($payload['dailyEndTime'] ?? '20:00'));
        $includeWeekends = (bool) ($payload['includeWeekends'] ?? true);
        $format = in_array(($payload['format'] ?? ''), ['onsite', 'remote'], true) ? (string) $payload['format'] : 'onsite';
        $capacity = max(1, (int) ($payload['capacity'] ?? 1));

        if ($endsAt <= $startsAt) {
            return ApiResponse::error('La date de fin doit être après la date de début.', Response::HTTP_BAD_REQUEST);
        }

        if ($dailyEndTime <= $dailyStartTime) {
            return ApiResponse::error("L'heure de fin journalière doit être après l'heure de début.", Response::HTTP_BAD_REQUEST);
        }

        $session = $id !== null ? $this->sessions->find($id) : null;
        if ($id !== null && $session === null) {
            return ApiResponse::error('Session introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($session === null) {
            $session = new TrainingSession($training, $format, $startsAt, $endsAt, $capacity);
            $this->em->persist($session);
        }

        $session
            ->setTraining($training)
            ->setFormat($format)
            ->setStartsAt($startsAt)
            ->setEndsAt($endsAt)
            ->setDailyStartTime($dailyStartTime)
            ->setDailyEndTime($dailyEndTime)
            ->setIncludeWeekends($includeWeekends)
            ->setLocation($this->nullableString($payload['location'] ?? null))
            ->setMeetingUrl($this->nullableString($payload['meetingUrl'] ?? null))
            ->setCapacity($capacity)
            ->setStatus(trim((string) ($payload['status'] ?? 'scheduled')) ?: 'scheduled');

        $this->em->flush();

        return ApiResponse::success($this->formatter->formatSession($session), $id === null ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }
}
