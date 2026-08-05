<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Application\Port\TrainingCategoryRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-categories', name: 'api_admin_training_categories_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingCategoriesController extends AbstractController
{
    public function __construct(private readonly TrainingCategoryRepositoryPort $categories, private readonly TrainingCategoryFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(fn ($category) => $this->formatter->format($category), $this->categories->findOrdered()),
        ]);
    }
}
