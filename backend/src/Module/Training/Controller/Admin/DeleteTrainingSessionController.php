<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingSessionRepository;
use App\Module\Training\Service\TrainingWriter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-sessions/{id}', name: 'api_admin_training_sessions_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteTrainingSessionController extends AbstractController
{
    public function __construct(private readonly TrainingSessionRepository $sessions, private readonly TrainingWriter $writer)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $session = $this->sessions->find($id);
        if (null === $session) {
            return ApiResponse::error('Session introuvable.', Response::HTTP_NOT_FOUND);
        }
        $this->writer->delete($session);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'La session a bien été supprimée.');
    }
}
