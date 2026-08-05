<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\PublicApi;

use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Application\Port\TrainingCategoryRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/training-categories', name: 'api_public_training_categories_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListTrainingCategoriesController extends AbstractController
{
    public function __construct(private readonly TrainingCategoryRepositoryPort $categories, private readonly TrainingCategoryFormatter $formatter)
    {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = Pagination::fromRequest($request, 25, 100);

        return ApiResponse::paginated(
            array_map(fn ($category) => $this->formatter->format($category), $this->categories->findOrdered(true, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->categories->countOrdered(true)),
        );
    }
}
