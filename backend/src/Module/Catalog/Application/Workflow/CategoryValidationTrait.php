<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Workflow;

use Symfony\Component\Validator\Constraints as Assert;

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
        $violations = $this->validator->validate(
            ['name' => $name, 'description' => $description],
            new Assert\Collection([
                'name' => [
                    new Assert\NotBlank(message: 'La categorie doit avoir un nom.'),
                    new Assert\Length(max: 150, maxMessage: 'Le nom ne doit pas depasser 150 caracteres.'),
                ],
                'description' => [
                    new Assert\Optional([
                        new Assert\Length(max: 2000, maxMessage: 'La description est trop longue.'),
                    ]),
                ],
            ])
        );

        if ($violations->count() > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
    }

    private function slugify(string $value): string
    {
        return $this->slugifyValue($value, 'categorie');
    }
}
