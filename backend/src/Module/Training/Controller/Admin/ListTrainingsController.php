<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainings', name: 'api_admin_trainings_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingsController extends AbstractController
{
    public function __construct(private readonly TrainingRepository $trainings, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $items = $this->trainings->findBy([], ['title' => 'ASC'], $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($training) => $this->formatter->formatTraining($training), $items),
            $pagination->metadata($this->trainings->count([])),
        );
    }
}
