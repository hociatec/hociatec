<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Module\Training\Application\DTO\TrainingSessionInput;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Writer\TrainingWriter;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Module\Training\Application\Port\TrainingSessionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly TrainingRepositoryPort $trainings,
        private readonly TrainingSessionRepositoryPort $sessions,
        private readonly TrainingFormatter $formatter,
        private readonly TrainingWriter $writer,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = TrainingSessionInput::fromArray($payload);
        $this->validator->validate($input);
        if ($input->endsAt <= $input->startsAt) {
            return ApiResponse::error('La date de fin doit être après la date de début.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($input->dailyEndTime <= $input->dailyStartTime) {
            return ApiResponse::error("L'heure de fin journalière doit être après l'heure de début.", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $training = $this->trainings->find($input->trainingId);
        if (null === $training) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $session = null !== $id ? $this->sessions->find($id) : null;
        if (null !== $id && null === $session) {
            return ApiResponse::error('Session introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (null === $session) {
            $session = new TrainingSession($training, $input->format, $input->startsAt, $input->endsAt, $input->capacity);
        }

        $session
            ->setTraining($training)
            ->setFormat($input->format)
            ->setStartsAt($input->startsAt)
            ->setEndsAt($input->endsAt)
            ->setDailyStartTime($input->dailyStartTime)
            ->setDailyEndTime($input->dailyEndTime)
            ->setIncludeWeekends($input->includeWeekends)
            ->setLocation($input->location)
            ->setMeetingUrl($input->meetingUrl)
            ->setCapacity($input->capacity)
            ->setStatus($input->status);

        $this->writer->save($session);

        return ApiResponse::success(
            $this->formatter->formatSession($session),
            null === $id ? Response::HTTP_CREATED : Response::HTTP_OK,
            null === $id ? 'La session a bien été créée.' : 'La session a bien été mise à jour.',
        );
    }
}
