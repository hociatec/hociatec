<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainings/{id}', name: 'api_admin_trainings_show', methods: ['GET'])]
#[IsGranted('ROLE_TRAINING_MANAGER')]
class ShowTrainingController extends AbstractController
{
    public function __construct(private readonly TrainingRepositoryPort $trainings, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $training = $this->trainings->find($id);
        if (null === $training) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($this->formatter->formatTraining($training));
    }
}
