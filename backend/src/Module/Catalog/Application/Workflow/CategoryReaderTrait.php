<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Category;

trait CategoryReaderTrait
{
    /**
     * @return list<Category>
     */
    public function listVisible(int $limit = 50, int $offset = 0): array
    {
        return $this->categoryRepository->findAllVisibleOrdered($limit, $offset);
    }

    public function countVisible(): int
    {
        return $this->categoryRepository->countVisible();
    }

    public function findVisibleBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findOneVisibleBySlug($slug);
    }

    /**
     * @return list<Category>
     */
    public function listForAdmin(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        return $this->categoryRepository->findAllForAdmin($limit, $offset, $search);
    }

    public function countForAdmin(?string $search = null): int
    {
        return $this->categoryRepository->countForAdmin($search);
    }
}
