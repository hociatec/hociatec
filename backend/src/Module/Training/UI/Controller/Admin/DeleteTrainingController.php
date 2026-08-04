<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Training\Application\Service\TrainingWriter;
use App\Module\Training\Infrastructure\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainings/{id}', name: 'api_admin_trainings_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteTrainingController extends AbstractController
{
    public function __construct(private readonly TrainingRepository $trainings, private readonly TrainingWriter $writer)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $training = $this->trainings->find($id);
        if (null === $training) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->writer->delete($training);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'La formation a bien été supprimée.');
    }
}
