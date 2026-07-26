<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingCategoryRepository;
use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Service\TrainingWriter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-categories/{id}', name: 'api_admin_training_categories_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteTrainingCategoryController extends AbstractController
{
    public function __construct(
        private readonly TrainingCategoryRepository $categories,
        private readonly TrainingRepository $trainings,
        private readonly TrainingWriter $writer,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $category = $this->categories->find($id);
        if (null === $category) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($this->trainings->count(['category' => $category->getSlug()]) > 0) {
            return ApiResponse::error('Cette catégorie est utilisée par des formations.', Response::HTTP_BAD_REQUEST);
        }

        $this->writer->delete($category);

        return ApiResponse::success(['deleted' => true], JsonResponse::HTTP_OK, 'La catégorie a bien été supprimée.');
    }
}
