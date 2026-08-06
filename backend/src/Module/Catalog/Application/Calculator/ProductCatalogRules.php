<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Calculator;

use App\Module\Catalog\Application\DTO\ProductCoreWriteData;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Shared\Application\Text\Slugifier;

final readonly class ProductCatalogRules
{
    use Slugifier;

    public function __construct(
        private ProductCatalogRepository $productRepository,
    ) {
    }

    public function assertValidData(ProductCoreWriteData $core, string $normalizedSku): void
    {
        $errors = [];
        if ('' === trim($core->name)) {
            $errors[] = 'Le produit doit avoir un nom.';
        }
        if (mb_strlen($core->name) > 180) {
            $errors[] = 'Le nom ne doit pas depasser 180 caracteres.';
        }
        if ('' === trim($normalizedSku)) {
            $errors[] = 'Le SKU est obligatoire.';
        }
        if (mb_strlen($normalizedSku) > 60) {
            $errors[] = 'Le SKU ne doit pas depasser 60 caracteres.';
        }
        if (1 !== preg_match('/^[A-Z0-9\-_]+$/i', $normalizedSku)) {
            $errors[] = 'Le SKU ne peut contenir que des lettres, chiffres, tirets et underscores.';
        }
        if ('' === trim($core->description)) {
            $errors[] = 'La description detaillee est obligatoire.';
        }
        if (null !== $core->shortDescription && mb_strlen($core->shortDescription) > 255) {
            $errors[] = 'Le resume est trop long.';
        }
        if ($core->priceCents < 0) {
            $errors[] = 'Le prix doit etre positif.';
        }
        if ($core->priceCents > 100000000) {
            $errors[] = 'Le prix indique est trop eleve.';
        }
        if ($core->stock < 0) {
            $errors[] = 'Le stock doit etre positif.';
        }
        if ($core->stock > 1000000) {
            $errors[] = 'Le stock est trop eleve.';
        }
        if ([] !== $errors) {
            throw new \InvalidArgumentException(implode("\n", $errors));
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
        return $this->slugifyValue($value, 'produit');
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
