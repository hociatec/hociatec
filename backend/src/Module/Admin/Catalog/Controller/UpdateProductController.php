<?php

declare(strict_types=1);

namespace App\Module\Admin\Catalog\Controller;

use App\Module\Catalog\Repository\CategoryRepository;
use App\Module\Catalog\Repository\ProductRepository;
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

#[Route('/api/admin/catalog/products/{id}', name: 'api_admin_catalog_products_update', methods: ['PUT', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $name = trim((string) $request->request->get('name', $product->getName()));
        $sku = strtoupper(trim((string) $request->request->get('sku', $product->getSku())));
        $description = (string) $request->request->get('description', $product->getDescription());
        $shortDescription = $request->request->get('shortDescription', $product->getShortDescription());
        $priceRaw = $request->request->get('price', $product->getPriceCents() / 100);
        $stock = (int) $request->request->get('stock', $product->getStock());
        $isPublished = $this->normalizeBoolean($request->request->get('isPublished', $product->isPublished()));
        $isFeaturedHome = $this->normalizeBoolean(
            $request->request->get('isFeaturedHome', $product->isFeaturedHome())
        );
        $categoryId = (int) $request->request->get('categoryId', $product->getCategory()->getId());
        $imageAlt = $request->request->get('imageAlt', $product->getImageAlt());
        $removeImage = $this->normalizeBoolean($request->request->get('removeImage', false));
        $slugValue = $request->request->get('slug', $product->getSlug());
        $slug = $slugValue !== null && $slugValue !== '' ? (string) $slugValue : null;
        $sellingType = (string) ($request->request->get('sellingType', $product->getSellingType()));

        $priceCents = $this->normalizePriceToCents($priceRaw);

        // Discounts
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

        $discountStartsAt = null; $discountEndsAt = null;
        if (is_string($discountStartsAtRaw) && $discountStartsAtRaw !== '') {
            $discountStartsAt = new \DateTimeImmutable($discountStartsAtRaw);
        }
        if (is_string($discountEndsAtRaw) && $discountEndsAtRaw !== '') {
            $discountEndsAt = new \DateTimeImmutable($discountEndsAtRaw);
        }

        if ($priceCents < 0) {
            return ApiResponse::error('Le prix doit etre positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category = $this->categoryRepository->find($categoryId);

        if ($category === null) {
            return ApiResponse::error('Categorie introuvable.', Response::HTTP_NOT_FOUND);
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

        $rawRemoveGallery = $request->request->all('removeGallery');
        if ($rawRemoveGallery === []) {
            $singleRemoveGallery = $request->request->get('removeGallery');
            if ($singleRemoveGallery !== null) {
                $rawRemoveGallery = is_array($singleRemoveGallery)
                    ? $singleRemoveGallery
                    : [$singleRemoveGallery];
            }
        }

        $galleryToRemove = [];
        foreach ($rawRemoveGallery as $value) {
            if (is_array($value)) {
                $value = reset($value);
            }

            if (is_numeric($value)) {
                $galleryToRemove[] = (int) $value;
            }
        }

        try {
            $product = $this->productService->update(
                $product,
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
                $galleryToRemove,
                $removeImage,
                $sellingType,
                $discountEnabled,
                $discountTypeNorm === 'fixed' ? 'fixed_cents' : $discountTypeNorm,
                $discountValue,
                $discountStartsAt,
                $discountEndsAt,
            );
        } catch (Throwable $exception) {
            return ApiResponse::error(
                'Impossible de mettre a jour le produit.',
                Response::HTTP_BAD_REQUEST,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success(CatalogFormatter::formatProduct($product, true));
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
}
