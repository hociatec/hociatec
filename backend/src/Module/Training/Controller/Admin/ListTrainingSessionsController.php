<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingSessionRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-sessions', name: 'api_admin_training_sessions_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingSessionsController extends AbstractController
{
    public function __construct(private readonly TrainingSessionRepository $sessions, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(fn ($session) => $this->formatter->formatSession($session), $this->sessions->findBy([], ['startsAt' => 'DESC'])),
        ]);
    }
}
