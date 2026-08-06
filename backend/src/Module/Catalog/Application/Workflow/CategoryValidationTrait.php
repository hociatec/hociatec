<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

trait CategoryValidationTrait
{
    private function generateUniqueSlug(string $name, ?int $excludeId): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $attempt = 1;

        while ($this->categoryRepository->existsWithSlug($slug, $excludeId)) {
            ++$attempt;
            $slug = sprintf('%s-%d', $baseSlug, $attempt);
        }

        return $slug;
    }

    private function assertUniqueName(string $name, ?int $excludeId): void
    {
        if ($this->categoryRepository->existsWithName($name, $excludeId)) {
            throw new \InvalidArgumentException('Une categorie avec ce nom existe déjà.');
        }
    }

    private function resolveSlug(?string $requestedSlug, string $name, ?int $excludeId): string
    {
        if (null !== $requestedSlug && '' !== trim($requestedSlug)) {
            $normalized = $this->slugify($requestedSlug);

            if ('' === $normalized) {
                throw new \InvalidArgumentException('Le slug fourni est invalide.');
            }

            if ($this->categoryRepository->existsWithSlug($normalized, $excludeId)) {
                throw new \InvalidArgumentException('Ce slug est déjà utilisé. Veuillez en choisir un autre.');
            }

            return $normalized;
        }

        return $this->generateUniqueSlug($name, $excludeId);
    }

    private function assertValidData(string $name, ?string $description): void
    {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('La categorie doit avoir un nom.');
        }
        if (mb_strlen($name) > 150) {
            throw new \InvalidArgumentException('Le nom ne doit pas depasser 150 caracteres.');
        }
        if (null !== $description && mb_strlen($description) > 2000) {
            throw new \InvalidArgumentException('La description est trop longue.');
        }
    }

    private function slugify(string $value): string
    {
        return $this->slugifyValue($value, 'categorie');
    }
}
