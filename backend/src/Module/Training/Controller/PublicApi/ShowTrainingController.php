<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\PublicApi;

use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Repository\TrainingSessionRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trainings/{slug}', name: 'api_public_trainings_show', methods: ['GET'])]
#[RateLimited('public_api')]
class ShowTrainingController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepository $trainings,
        private readonly TrainingSessionRepository $sessions,
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
