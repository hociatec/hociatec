<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingSessionRepository;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-sessions/{id}', name: 'api_admin_training_sessions_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteTrainingSessionController extends AbstractController
{
    public function __construct(private readonly TrainingSessionRepository $sessions, private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $session = $this->sessions->find($id);
        if (null === $session) {
            return ApiResponse::error('Session introuvable.', Response::HTTP_NOT_FOUND);
        }
        $this->em->remove($session);
        $this->em->flush();

        return ApiResponse::success(['deleted' => true]);
    }
}
