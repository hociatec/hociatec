<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\PublicApi;

use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Module\Training\Application\Port\TrainingSessionRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trainings/{slug}', name: 'api_public_trainings_show', methods: ['GET'])]
#[RateLimited('public_api')]
class ShowTrainingController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepositoryPort $trainings,
        private readonly TrainingSessionRepositoryPort $sessions,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(string $slug): JsonResponse
    {
        $training = $this->trainings->findOneBy(['slug' => $slug, 'isActive' => true]);
        if (null === $training) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'training' => $this->formatter->formatTraining($training),
            'sessions' => array_map(
                fn ($session) => $this->formatter->formatSession($session),
                $this->sessions->findUpcomingForTraining($training),
            ),
        ]);
    }
}
