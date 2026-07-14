<?php

declare(strict_types=1);

namespace App\Module\Catalog\Service;

use App\Module\Catalog\Entity\Brand;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return list<Product>
     */
    public function listForAdmin(): array
    {
        return $this->productRepository->findAllForAdmin();
    }

    /**
     * @return list<Product>
     */
    public function listPublished(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
        ?string $sort = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        return $this->productRepository->findPublished(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
            $sort,
            $limit,
            $offset,
        );
    }

    public function countPublished(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): int {
        return $this->productRepository->countPublished(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function collectPublishedFacets(
        ?string $categorySlug,
        ?string $search,
        ?bool $onlyFeatured = null,
        ?string $sellingType = null,
        ?string $brand = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        ?int $minPriceCents = null,
        ?int $maxPriceCents = null,
        ?bool $inStockOnly = null,
    ): array {
        return $this->productRepository->collectPublishedFacets(
            $categorySlug,
            $search,
            $onlyFeatured,
            $sellingType,
            $brand,
            $storageCapacity,
            $memoryRam,
            $color,
            $minPriceCents,
            $maxPriceCents,
            $inStockOnly,
        );
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return $this->productRepository->findOnePublishedBySlug($slug);
    }

    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     */
    public function create(
        string $name,
        string $sku,
        ?string $slug,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        array $galleryFiles,
        ?string $imageAlt,
        ?string $sellingType = 'sale',
        ?Brand $brand = null,
        ?string $variantGroup = null,
        ?int $releaseYear = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        array $variantDefinitions = [],
        ?bool $discountEnabled = null,
        ?string $discountType = null,
        ?int $discountValue = null,
        ?\DateTimeImmutable $discountStartsAt = null,
        ?\DateTimeImmutable $discountEndsAt = null,
    ): Product {
        $normalizedSku = strtoupper($sku);
        $resolvedVariantGroup = $this->resolveVariantGroup($variantGroup, $name, $variantDefinitions);

        $this->assertValidData($name, $normalizedSku, $description, $shortDescription, $priceCents, $stock);
        $this->assertUniqueness($normalizedSku, null);
        $this->assertVariantDefinitionsAreUnique($resolvedVariantGroup, null, $color, $storageCapacity, $variantDefinitions);

        $resolvedSlug = $this->resolveSlug($slug, $name, null);

        $product = new Product(
            $name,
            $resolvedSlug,
            $normalizedSku,
            $description,
            $priceCents,
            $stock,
            $category,
        );

        $product
            ->setShortDescription($shortDescription)
            ->setIsPublished($isPublished)
            ->setIsFeaturedHome($isFeaturedHome)
            ->setImageAlt($imageAlt)
            ->setBrandReference($brand)
            ->setVariantGroup($resolvedVariantGroup)
            ->setVariantPosition(1)
            ->setReleaseYear($releaseYear)
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($memoryRam)
            ->setColor($color);

        if ($sellingType !== null) {
            $product->setSellingType($sellingType);
        }

        if ($discountEnabled !== null) {
            $product->setDiscountEnabled($discountEnabled);
        }
        if ($discountType !== null) {
            $product->setDiscountType($discountType);
        }
        if ($discountValue !== null) {
            $product->setDiscountValue($discountValue);
        }
        if ($discountStartsAt !== null) {
            $product->setDiscountStartsAt($discountStartsAt);
        }
        if ($discountEndsAt !== null) {
            $product->setDiscountEndsAt($discountEndsAt);
        }

        $this->hydrateGallery($product, $galleryFiles, []);

        $this->entityManager->persist($product);

        foreach ($variantDefinitions as $index => $variantDefinition) {
            if (!is_array($variantDefinition)) {
                continue;
            }

            $variantStock = isset($variantDefinition['stock']) ? (int) $variantDefinition['stock'] : $stock;
            $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
                ? trim($variantDefinition['color'])
                : null;
            $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
                ? trim($variantDefinition['storageCapacity'])
                : null;

            if ($variantStock < 0) {
                continue;
            }

            if (($variantColor === null || $variantColor === '') && ($variantStorage === null || $variantStorage === '')) {
                continue;
            }

            $variantProduct = $this->createVariantCopy(
                $product,
                $name,
                $sku,
                $slug,
                $resolvedVariantGroup,
                $variantColor !== null && $variantColor !== '' ? $variantColor : null,
                $variantStorage !== null && $variantStorage !== '' ? $variantStorage : null,
                $variantStock,
                $index + 2
            );

            $this->entityManager->persist($variantProduct);
        }

        $this->entityManager->flush();

        return $product;
    }

    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param array<int, int|string> $galleryToRemove
     */
    public function update(
        Product $product,
        string $name,
        string $sku,
        ?string $slug,
        string $description,
        ?string $shortDescription,
        int $priceCents,
        int $stock,
        bool $isPublished,
        bool $isFeaturedHome,
        Category $category,
        array $galleryFiles,
        ?string $imageAlt,
        array $galleryToRemove = [],
        bool $removeImage = false,
        ?string $sellingType = null,
        ?Brand $brand = null,
        ?string $variantGroup = null,
        ?int $releaseYear = null,
        ?string $storageCapacity = null,
        ?string $memoryRam = null,
        ?string $color = null,
        array $variantDefinitions = [],
        ?bool $discountEnabled = null,
        ?string $discountType = null,
        ?int $discountValue = null,
        ?\DateTimeImmutable $discountStartsAt = null,
        ?\DateTimeImmutable $discountEndsAt = null,
    ): Product {
        $normalizedSku = strtoupper($sku);
        $resolvedVariantGroup = $variantGroup ?? $product->getVariantGroup() ?? $this->buildVariantGroupLabel($name);

        $this->assertValidData($name, $normalizedSku, $description, $shortDescription, $priceCents, $stock);
        $this->assertUniqueness($normalizedSku, $product->getId());
        $this->assertVariantDefinitionsAreUnique($resolvedVariantGroup, $product, $color, $storageCapacity, $variantDefinitions);

        $resolvedSlug = $this->resolveSlug($slug, $name, $product->getId());

        $product
            ->setName($name)
            ->setSlug($resolvedSlug)
            ->setSku($normalizedSku)
            ->setDescription($description)
            ->setShortDescription($shortDescription)
            ->setPriceCents($priceCents)
            ->setStock($stock)
            ->setIsPublished($isPublished)
            ->setIsFeaturedHome($isFeaturedHome)
            ->setCategory($category)
            ->setImageAlt($imageAlt)
            ->setBrandReference($brand)
            ->setVariantGroup($resolvedVariantGroup)
            ->setVariantPosition($product->getVariantPosition() > 0 ? $product->getVariantPosition() : 1)
            ->setReleaseYear($releaseYear)
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($memoryRam)
            ->setColor($color);

        if ($sellingType !== null) {
            $product->setSellingType($sellingType);
        }

        if ($discountEnabled !== null) {
            $product->setDiscountEnabled($discountEnabled);
        }
        if ($discountType !== null) {
            $product->setDiscountType($discountType);
        }
        if ($discountValue !== null) {
            $product->setDiscountValue($discountValue);
        }
        if ($discountStartsAt !== null || $discountEndsAt !== null) {
            $product->setDiscountStartsAt($discountStartsAt);
            $product->setDiscountEndsAt($discountEndsAt);
        }

        if ($removeImage) {
            $galleryToRemove[] = 0;
        }

        $this->hydrateGallery($product, $galleryFiles, $galleryToRemove);

        if ($variantDefinitions !== [] && $resolvedVariantGroup !== null && $resolvedVariantGroup !== '') {
            $existingVariants = $this->productRepository->findByVariantGroupOrdered($resolvedVariantGroup);
            $nextVariantPosition = count($existingVariants) + 1;

            foreach ($variantDefinitions as $variantDefinition) {
                if (!is_array($variantDefinition)) {
                    continue;
                }

                $variantStock = isset($variantDefinition['stock']) ? (int) $variantDefinition['stock'] : $stock;
                $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
                    ? trim($variantDefinition['color'])
                    : null;
                $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
                    ? trim($variantDefinition['storageCapacity'])
                    : null;

                if ($variantStock < 0) {
                    continue;
                }

                if (($variantColor === null || $variantColor === '') && ($variantStorage === null || $variantStorage === '')) {
                    continue;
                }

                $variantProduct = $this->createVariantCopy(
                    $product,
                    $name,
                    $sku,
                    $slug,
                    $resolvedVariantGroup,
                    $variantColor !== null && $variantColor !== '' ? $variantColor : null,
                    $variantStorage !== null && $variantStorage !== '' ? $variantStorage : null,
                    $variantStock,
                    $nextVariantPosition
                );

                $this->entityManager->persist($variantProduct);
                ++$nextVariantPosition;
            }
        }

        $this->entityManager->flush();

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $variantDefinition
     */
    private function createVariantCopy(
        Product $template,
        string $baseName,
        string $baseSku,
        ?string $baseSlug,
        string $variantGroup,
        ?string $color,
        ?string $storageCapacity,
        int $stock,
        int $index,
    ): Product {
        $variantName = $this->buildVariantName($baseName, $color, $storageCapacity);
        $variantSku = $this->buildVariantSku($baseSku, $color, $storageCapacity, $index);
        $variantSlug = $this->buildVariantSlug($baseSlug ?? $baseName, $color, $storageCapacity, $index);

        $variantProduct = new Product(
            $variantName,
            $variantSlug,
            $variantSku,
            $template->getDescription(),
            $template->getPriceCents(),
            $stock,
            $template->getCategory(),
        );

        $variantProduct
            ->setShortDescription($template->getShortDescription())
            ->setIsPublished($template->isPublished())
            ->setIsFeaturedHome($template->isFeaturedHome())
            ->setImageAlt($template->getImageAlt())
            ->setBrandReference($template->getBrandReference())
            ->setVariantGroup($variantGroup)
            ->setVariantPosition($index)
            ->setReleaseYear($template->getReleaseYear())
            ->setStorageCapacity($storageCapacity)
            ->setMemoryRam($template->getMemoryRam())
            ->setColor($color)
            ->setSellingType($template->getSellingType())
            ->setDiscountEnabled($template->isDiscountEnabled())
            ->setDiscountType($template->getDiscountType())
            ->setDiscountValue($template->getDiscountValue())
            ->setDiscountStartsAt($template->getDiscountStartsAt())
            ->setDiscountEndsAt($template->getDiscountEndsAt())
            ->setImageName($template->getImageName())
            ->setImageSize($template->getImageSize())
            ->setGalleryImage2Name($template->getGalleryImage2Name())
            ->setGalleryImage2Size($template->getGalleryImage2Size())
            ->setGalleryImage3Name($template->getGalleryImage3Name())
            ->setGalleryImage3Size($template->getGalleryImage3Size())
            ->setGalleryImage4Name($template->getGalleryImage4Name())
            ->setGalleryImage4Size($template->getGalleryImage4Size());

        return $variantProduct;
    }

    /**
     * @param array<int, mixed> $variantDefinitions
     */
    private function resolveVariantGroup(?string $variantGroup, string $name, array $variantDefinitions): ?string
    {
        $normalized = $variantGroup !== null ? trim($variantGroup) : '';

        if ($normalized !== '') {
            return $normalized;
        }

        if ($variantDefinitions !== []) {
            return $this->buildVariantGroupLabel($name);
        }

        return $this->buildVariantGroupLabel($name);
    }

    private function buildVariantGroupLabel(string $name): string
    {
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($name)) ?? trim($name);
        $label = preg_replace('/\s*\([^)]*\)\s*$/u', '', $label) ?? $label;
        $label = trim($label);

        return $label !== '' ? $label : $name;
    }

    private function buildVariantName(string $baseName, ?string $color, ?string $storageCapacity): string
    {
        $parts = [];

        if ($color !== null && $color !== '') {
            $parts[] = $color;
        }

        if ($storageCapacity !== null && $storageCapacity !== '') {
            $parts[] = $storageCapacity;
        }

        if ($parts === []) {
            return $baseName;
        }

        return sprintf('%s (%s)', $baseName, implode(') (', $parts));
    }

    private function buildVariantSku(string $baseSku, ?string $color, ?string $storageCapacity, int $index): string
    {
        $suffix = $this->slugify(trim((string) $color . ' ' . (string) $storageCapacity));
        if ($suffix === 'produit') {
            $suffix = (string) ($index + 1);
        }

        return strtoupper(sprintf('%s-%s', $baseSku, $suffix));
    }

    private function buildVariantSlug(string $baseSlugOrName, ?string $color, ?string $storageCapacity, int $index): string
    {
        $base = $this->slugify($baseSlugOrName);
        $suffix = $this->slugify(trim((string) $color . ' ' . (string) $storageCapacity));

        if ($suffix === 'produit') {
            $suffix = (string) ($index + 1);
        }

        $candidate = sprintf('%s-%s', $base, $suffix);
        $attempt = 1;

        while ($this->productRepository->existsWithSlug($candidate, null)) {
            ++$attempt;
            $candidate = sprintf('%s-%s-%d', $base, $suffix, $attempt);
        }

        return $candidate;
    }

    private function assertValidData(
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
            throw new InvalidArgumentException((string) $violations);
        }
    }

    private function assertUniqueness(string $sku, ?int $excludeId): void
    {
        if ($this->productRepository->existsWithSku($sku, $excludeId)) {
            throw new InvalidArgumentException('Ce SKU est déjà utilise par un autre produit.');
        }
    }

    /**
     * @param array<int, mixed> $variantDefinitions
     */
    private function assertVariantDefinitionsAreUnique(
        ?string $variantGroup,
        ?Product $currentProduct,
        ?string $currentColor,
        ?string $currentStorageCapacity,
        array $variantDefinitions,
    ): void {
        if ($variantGroup === null || trim($variantGroup) === '') {
            return;
        }

        $existingKeys = [];

        foreach ($this->productRepository->findByVariantGroupOrdered($variantGroup) as $variant) {
            if ($currentProduct !== null && $variant->getId() === $currentProduct->getId()) {
                continue;
            }

            $existingKeys[$this->buildVariantIdentityKey($variant->getColor(), $variant->getStorageCapacity())] = true;
        }

        $currentKey = $this->buildVariantIdentityKey($currentColor, $currentStorageCapacity);

        if (isset($existingKeys[$currentKey])) {
            throw new InvalidArgumentException(sprintf(
                'La variante %s existe déjà.',
                $this->formatVariantConflictLabel($currentColor, $currentStorageCapacity)
            ));
        }

        $incomingKeys = [$currentKey => true];

        foreach ($variantDefinitions as $variantDefinition) {
            if (!is_array($variantDefinition)) {
                continue;
            }

            $variantColor = isset($variantDefinition['color']) && is_string($variantDefinition['color'])
                ? trim($variantDefinition['color'])
                : null;
            $variantStorage = isset($variantDefinition['storageCapacity']) && is_string($variantDefinition['storageCapacity'])
                ? trim($variantDefinition['storageCapacity'])
                : null;

            if (($variantColor === null || $variantColor === '') && ($variantStorage === null || $variantStorage === '')) {
                continue;
            }

            $variantKey = $this->buildVariantIdentityKey($variantColor, $variantStorage);

            if (isset($existingKeys[$variantKey]) || isset($incomingKeys[$variantKey])) {
                throw new InvalidArgumentException(sprintf(
                    'La variante %s existe déjà.',
                    $this->formatVariantConflictLabel($variantColor, $variantStorage)
                ));
            }

            $incomingKeys[$variantKey] = true;
        }
    }

    private function buildVariantIdentityKey(?string $color, ?string $storageCapacity): string
    {
        $normalizedColor = $color !== null ? mb_strtolower(trim($color)) : '';
        $normalizedStorage = $storageCapacity !== null ? mb_strtolower(trim($storageCapacity)) : '';

        return sprintf('%s|%s', $normalizedColor, $normalizedStorage);
    }

    private function formatVariantConflictLabel(?string $color, ?string $storageCapacity): string
    {
        $parts = array_values(array_filter([
            $color !== null ? trim($color) : '',
            $storageCapacity !== null ? trim($storageCapacity) : '',
        ]));

        return $parts !== [] ? implode(' / ', $parts) : 'cette variante';
    }

    private function assertUniqueSlug(string $slug, ?int $excludeId): void
    {
        if ($this->productRepository->existsWithSlug($slug, $excludeId)) {
            throw new InvalidArgumentException('Ce slug est déjà utilisé. Veuillez en choisir un autre.');
        }
    }

    private function resolveSlug(?string $requestedSlug, string $name, ?int $excludeId): string
    {
        if ($requestedSlug !== null && trim($requestedSlug) !== '') {
            $normalized = $this->slugify($requestedSlug);

            if ($normalized === '') {
                throw new InvalidArgumentException('Le slug fourni est invalide.');
            }

            $this->assertUniqueSlug($normalized, $excludeId);

            return $normalized;
        }

        return $this->generateUniqueSlug($name, $excludeId);
    }

    /**
     * @param array<int, UploadedFile|null> $galleryFiles
     * @param array<int, int|string> $galleryToRemove
     */
    private function hydrateGallery(Product $product, array $galleryFiles, array $galleryToRemove): void
    {
        $removals = array_unique(array_map(static fn ($value) => (int) $value, $galleryToRemove));

        foreach ($galleryFiles as $index => $file) {
            $intIndex = (int) $index;

            if ($intIndex < 0 || $intIndex > 3) {
                continue;
            }

            if ($file instanceof UploadedFile) {
                $product->setGalleryImageFile($intIndex, $file);
                $removals = array_values(array_filter(
                    $removals,
                    static fn (int $value) => $value !== $intIndex
                ));
            }
        }

        foreach ($removals as $index) {
            if ($index < 0 || $index > 3) {
                continue;
            }

            $product->removeGalleryImage($index);
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

    private function slugify(string $value): string
    {
        $value = trim($value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value !== '' ? $value : 'produit';
    }
}
