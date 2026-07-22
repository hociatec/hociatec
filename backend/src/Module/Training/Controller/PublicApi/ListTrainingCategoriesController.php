<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\PublicApi;

use App\Module\Training\Repository\TrainingCategoryRepository;
use App\Module\Training\Service\TrainingCategoryFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/training-categories', name: 'api_public_training_categories_list', methods: ['GET'])]
#[RateLimiter('public_api')]
class ListTrainingCategoriesController extends AbstractController
{
    public function __construct(private readonly TrainingCategoryRepository $categories, private readonly TrainingCategoryFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(fn ($category) => $this->formatter->format($category), $this->categories->findOrdered(true)),
        ]);
    }
}
