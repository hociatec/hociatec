<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\PublicApi;

use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trainings', name: 'api_public_trainings_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListTrainingsController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepositoryPort $trainings,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $category = trim((string) $request->query->get('category', ''));
        $pagination = Pagination::fromRequest($request, 20, 50);
        $category = '' !== $category ? $category : null;
        $total = $this->trainings->countActive($category);

        return ApiResponse::paginated(
            array_map(
                fn ($training) => $this->formatter->formatTraining($training),
                $this->trainings->findActivePaginated($category, $pagination->perPage, $pagination->offset()),
            ),
            $pagination->metadata($total),
        );
    }
}
