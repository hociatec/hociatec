<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\BrandRepository;
use App\Module\Catalog\Repository\CategoryRepository;
use App\Module\Catalog\Service\CatalogFormatter;
use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/api/admin/catalog/products', name: 'api_admin_catalog_products_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class CreateProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryRepository $categoryRepository,
        private readonly BrandRepository $brandRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $name = trim((string) $request->request->get('name', ''));
        $sku = strtoupper(trim((string) $request->request->get('sku', '')));
        $description = (string) $request->request->get('description', '');
        $shortDescription = $request->request->get('shortDescription');
        $priceRaw = $request->request->get('price', 0);
        $stock = (int) $request->request->get('stock', 0);
        $categoryId = (int) $request->request->get('categoryId', 0);
        $brandId = $this->normalizeOptionalInt($request->request->get('brandId'));
        $isPublished = $this->normalizeBoolean($request->request->get('isPublished', '1'));
        $isFeaturedHome = $this->normalizeBoolean($request->request->get('isFeaturedHome', false));
        $slugValue = $request->request->get('slug');
        $slug = $slugValue !== null && $slugValue !== '' ? (string) $slugValue : null;
        $imageAlt = $request->request->get('imageAlt');
        $variantGroupValue = $request->request->get('variantGroup');
        $variantGroup = is_string($variantGroupValue) && trim($variantGroupValue) !== '' ? trim($variantGroupValue) : null;
        $releaseYear = $this->normalizeOptionalInt($request->request->get('releaseYear'));
        $storageCapacityValue = $request->request->get('storageCapacity');
        $storageCapacity = is_string($storageCapacityValue) && trim($storageCapacityValue) !== '' ? trim($storageCapacityValue) : null;
        $memoryRamValue = $request->request->get('memoryRam');
        $memoryRam = is_string($memoryRamValue) && trim($memoryRamValue) !== '' ? trim($memoryRamValue) : null;
        $colorValue = $request->request->get('color');
        $color = is_string($colorValue) && trim($colorValue) !== '' ? trim($colorValue) : null;
        $variantsValue = $request->request->get('variants');
        $variantDefinitions = [];
        if (is_string($variantsValue) && trim($variantsValue) !== '') {
            $decodedVariants = json_decode($variantsValue, true);
            if (is_array($decodedVariants)) {
                foreach ($decodedVariants as $variantRow) {
                    if (!is_array($variantRow)) {
                        continue;
                    }

                    $variantDefinitions[] = [
                        'color' => isset($variantRow['color']) && is_string($variantRow['color']) ? trim($variantRow['color']) : null,
                        'storageCapacity' => isset($variantRow['storageCapacity']) && is_string($variantRow['storageCapacity']) ? trim($variantRow['storageCapacity']) : null,
                        'stock' => isset($variantRow['stock']) ? (int) $variantRow['stock'] : 0,
                    ];
                }
            }
        }
        $priceCents = $this->normalizePriceToCents($priceRaw);
        $sellingType = (string) ($request->request->get('sellingType', 'sale'));

        $discountEnabled = $this->normalizeBoolean($request->request->get('discountEnabled', false));
        $discountType = $request->request->get('discountType');
        $discountValueRaw = $request->request->get('discountValue');
        $discountStartsAtRaw = $request->request->get('discountStartsAt');
        $discountEndsAtRaw = $request->request->get('discountEndsAt');

        $discountTypeNorm = ($discountType !== null && $discountType !== '') ? (string) $discountType : null;
        if ($discountTypeNorm !== null && !in_array($discountTypeNorm, ['percent', 'fixed'], true)) {
            $discountTypeNorm = null;
        }
        $discountValue = null;
        if ($discountValueRaw !== null && $discountValueRaw !== '') {
            if ($discountTypeNorm === 'percent') {
                $discountValue = (int) round((float) str_replace(',', '.', (string) $discountValueRaw));
            } elseif ($discountTypeNorm === 'fixed') {
                $discountValue = $this->normalizePriceToCents($discountValueRaw);
            }
        }

        $discountStartsAt = null;
        $discountEndsAt = null;
        if (is_string($discountStartsAtRaw) && $discountStartsAtRaw !== '') {
            $discountStartsAt = new \DateTimeImmutable($discountStartsAtRaw);
        }
        if (is_string($discountEndsAtRaw) && $discountEndsAtRaw !== '') {
            $discountEndsAt = new \DateTimeImmutable($discountEndsAtRaw);
        }

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit être positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category = $this->categoryRepository->find($categoryId);

        if ($category === null) {
            return ApiResponse::error('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $brand = null;
        if ($brandId !== null) {
            $brand = $this->brandRepository->find($brandId);
            if ($brand === null) {
                return ApiResponse::error('Marque introuvable.', Response::HTTP_NOT_FOUND);
            }
        }

        $galleryPayload = $request->files->get('gallery', []);
        if ($galleryPayload !== null && !is_array($galleryPayload)) {
            return ApiResponse::error('Galerie d\'images invalide.', Response::HTTP_BAD_REQUEST);
        }

        $galleryFiles = [];

        foreach (range(0, 3) as $index) {
            $file = $galleryPayload[$index] ?? null;

            if ($index === 0 && $file === null) {
                $file = $request->files->get('image');
            }

            if ($file !== null && !($file instanceof UploadedFile)) {
                return ApiResponse::error('Fichier d\'image invalide.', Response::HTTP_BAD_REQUEST);
            }

            $galleryFiles[$index] = $file;
        }

        try {
            $product = $this->productService->create(
                $name,
                $sku,
                $slug,
                $description,
                $shortDescription !== '' ? (string) $shortDescription : null,
                $priceCents,
                $stock,
                $isPublished,
                $isFeaturedHome,
                $category,
                $galleryFiles,
                $imageAlt !== '' ? (string) $imageAlt : null,
                $sellingType,
                $brand,
                $variantGroup,
                $releaseYear,
                $storageCapacity,
                $memoryRam,
                $color,
                $variantDefinitions,
                $discountEnabled,
                $discountTypeNorm === 'fixed' ? 'fixed_cents' : $discountTypeNorm,
                $discountValue,
                $discountStartsAt,
                $discountEndsAt,
            );
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de créer le produit.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created(CatalogFormatter::formatProduct($product, true));
    }

    private function normalizePriceToCents(mixed $price): int
    {
        if (is_int($price)) {
            return $price * 100;
        }

        if (is_float($price)) {
            return (int) round($price * 100);
        }

        if (is_string($price)) {
            $normalized = str_replace(',', '.', $price);

            if (is_numeric($normalized)) {
                return (int) round((float) $normalized * 100);
            }
        }

        return -1;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);

            return in_array($lower, ['1', 'true', 'on', 'yes'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return (bool) $value;
    }

    private function normalizeOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
