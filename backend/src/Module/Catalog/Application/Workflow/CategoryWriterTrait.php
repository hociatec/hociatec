<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Exception\CatalogOperationException;

trait CategoryWriterTrait
{
    public function create(string $name, ?string $slug, ?string $description, bool $isVisible): Category
    {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, null);

        $category = new Category($name, $this->resolveSlug($slug, $name, null));
        $category
            ->setDescription($description)
            ->setIsVisible($isVisible);

        try {
            $this->persistence->save($category);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de créer la catégorie.', $exception);
        }

        return $category;
    }

    public function update(
        Category $category,
        string $name,
        ?string $slug,
        ?string $description,
        bool $isVisible,
    ): Category {
        $this->assertValidData($name, $description);
        $this->assertUniqueName($name, $category->getId());

        $category
            ->setName($name)
            ->setSlug($this->resolveSlug($slug, $name, $category->getId()))
            ->setDescription($description)
            ->setIsVisible($isVisible);

        try {
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de mettre à jour la catégorie.', $exception);
        }

        return $category;
    }

    public function delete(Category $category): void
    {
        if (!$category->getProducts()->isEmpty()) {
            throw CatalogOperationException::invalidOperation('Impossible de supprimer la categorie car elle contient encore des produits.');
        }

        try {
            $this->persistence->delete($category);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw CatalogOperationException::failed('Impossible de supprimer la catégorie.', $exception);
        }
    }
}
