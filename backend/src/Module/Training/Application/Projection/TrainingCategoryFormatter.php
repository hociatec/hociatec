<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Projection;

use App\Module\Training\Domain\Entity\TrainingCategory;

final class TrainingCategoryFormatter
{
    /** @return array<string, mixed> */
    public function format(TrainingCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'position' => $category->getPosition(),
            'isActive' => $category->isActive(),
        ];
    }
}
