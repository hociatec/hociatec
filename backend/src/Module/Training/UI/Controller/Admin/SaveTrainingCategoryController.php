<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Training\Application\Service\TrainingCategoryFormatter;
use App\Module\Training\Application\Service\TrainingWriter;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-categories/{id}', name: 'api_admin_training_categories_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[Route('/api/admin/training-categories', name: 'api_admin_training_categories_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class SaveTrainingCategoryController extends AbstractController
{
    public function __construct(
        private readonly TrainingCategoryRepository $categories,
        private readonly TrainingWriter $writer,
        private readonly TrainingCategoryFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        $name = trim((string) ($payload['name'] ?? ''));
        if ('' === $name) {
            return ApiResponse::error('Le nom est requis.', Response::HTTP_BAD_REQUEST);
        }

        $category = null !== $id ? $this->categories->find($id) : null;
        if (null !== $id && null === $category) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $slug = $this->writer->slugify((string) ($payload['slug'] ?? $name));
        $existing = $this->categories->findOneBy(['slug' => $slug]);
        if (null !== $existing && $existing->getId() !== $category?->getId()) {
            return ApiResponse::error('Ce slug de catégorie existe déjà.', Response::HTTP_BAD_REQUEST);
        }

        if (null === $category) {
            $category = new TrainingCategory($name, $slug);
        }

        $category
            ->setName($name)
            ->setSlug($slug)
            ->setPosition((int) ($payload['position'] ?? $category->getPosition()))
            ->setIsActive((bool) ($payload['isActive'] ?? $category->isActive()));

        $this->writer->save($category);

        return ApiResponse::success(
            $this->formatter->format($category),
            null === $id ? Response::HTTP_CREATED : Response::HTTP_OK,
            null === $id ? 'La catégorie a bien été créée.' : 'La catégorie a bien été mise à jour.',
        );
    }
}
