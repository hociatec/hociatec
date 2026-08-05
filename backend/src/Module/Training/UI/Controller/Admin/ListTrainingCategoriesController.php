<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Application\Port\TrainingCategoryRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-categories', name: 'api_admin_training_categories_list', methods: ['GET'])]
#[IsGranted('ROLE_TRAINING_MANAGER')]
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
            array_map(fn ($category) => $this->formatter->format($category), $this->categories->findOrdered(false, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->categories->countOrdered()),
        );
    }
}
