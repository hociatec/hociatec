<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Training\Application\Service\TrainingFormatter;
use App\Module\Training\Infrastructure\Repository\TrainingSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-sessions', name: 'api_admin_training_sessions_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingSessionsController extends AbstractController
{
    public function __construct(private readonly TrainingSessionRepository $sessions, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $items = $this->sessions->findBy([], ['startsAt' => 'DESC'], $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($session) => $this->formatter->formatSession($session), $items),
            $pagination->metadata($this->sessions->count([])),
        );
    }
}
