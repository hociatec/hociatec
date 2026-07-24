<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Repository\ProductRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ProductCatalogRules
{
    public function __construct(
        private ProductRepository $productRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function assertValidData(
        string $name,
        string $sku,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
    ): void {
        $violations = $this->validator->validate(
            [
                'name' => $name,
                'sku' => $sku,
                'description' => $description,
                'shortDescription' => $shortDescription,
                'price' => $priceCents,
                'stock' => $stock,
            ],
            new Assert\Collection([
                'name' => [
                    new Assert\NotBlank(message: 'Le produit doit avoir un nom.'),
                    new Assert\Length(max: 180, maxMessage: 'Le nom ne doit pas depasser 180 caracteres.'),
                ],
                'sku' => [
                    new Assert\NotBlank(message: 'Le SKU est obligatoire.'),
                    new Assert\Length(
                        max: 60,
                        maxMessage: 'Le SKU ne doit pas depasser 60 caracteres.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[A-Z0-9\-_]+$/i',
                        message: 'Le SKU ne peut contenir que des lettres, chiffres, tirets et underscores.'
                    ),
                ],
                'description' => [
                    new Assert\NotBlank(message: 'La description detaillee est obligatoire.'),
                ],
                'shortDescription' => [
                    new Assert\Optional([
                        new Assert\Length(
                            max: 255,
                            maxMessage: 'Le resume est trop long.'
                        ),
                    ]),
                ],
                'price' => [
                    new Assert\GreaterThanOrEqual(value: 0, message: 'Le prix doit etre positif.'),
                    new Assert\LessThanOrEqual(
                        value: 100000000,
                        message: 'Le prix indique est trop eleve.'
                    ),
                ],
                'stock' => [
                    new Assert\GreaterThanOrEqual(value: 0, message: 'Le stock doit etre positif.'),
                    new Assert\LessThanOrEqual(value: 1000000, message: 'Le stock est trop eleve.'),
                ],
            ])
        );

        if ($violations->count() > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
    }

    public function assertUniqueness(string $sku, ?int $excludeId): void
    {
        if ($this->productRepository->existsWithSku($sku, $excludeId)) {
            throw new \InvalidArgumentException('Ce SKU est déjà utilise par un autre produit.');
        }
    }

    public function resolveSlug(?string $requestedSlug, string $name, ?int $excludeId): string
    {
        if (null !== $requestedSlug && '' !== trim($requestedSlug)) {
            $normalized = $this->slugify($requestedSlug);

            if ('' === $normalized) {
                throw new \InvalidArgumentException('Le slug fourni est invalide.');
            }

            $this->assertUniqueSlug($normalized, $excludeId);

            return $normalized;
        }

        return $this->generateUniqueSlug($name, $excludeId);
    }

    public function slugify(string $value): string
    {
        $value = trim($value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
        $value = trim($value, '-');

        return '' !== $value ? $value : 'produit';
    }

    private function assertUniqueSlug(string $slug, ?int $excludeId): void
    {
        if ($this->productRepository->existsWithSlug($slug, $excludeId)) {
            throw new \InvalidArgumentException('Ce slug est déjà utilisé. Veuillez en choisir un autre.');
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $attempt = 1;

        while ($this->productRepository->existsWithSlug($slug, $excludeId)) {
            ++$attempt;
            $slug = sprintf('%s-%d', $baseSlug, $attempt);
        }

        return $slug;
    }
}
