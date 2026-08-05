<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\Mapper;

use App\Module\Admin\Application\Catalog\DTO\ProductWriteData;
use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Module\Admin\Application\Catalog\Parser\ProductVariantPayloadParser;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Application\Port\BrandRepositoryPort;
use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ProductFormRequestMapper
{
    public function __construct(
        private CategoryRepositoryPort $categories,
        private BrandRepositoryPort $brands,
        private ProductVariantPayloadParser $variants,
        private ProductGalleryRequestMapper $gallery,
        private ProductDiscountRequestMapper $discount,
    ) {
    }

    public function create(Request $request): ProductWriteData
    {
        return $this->map($request, null);
    }

    public function update(Request $request, Product $product): ProductWriteData
    {
        return $this->map($request, $product);
    }

    private function map(Request $request, ?Product $product): ProductWriteData
    {
        try {
            $priceCents = ProductFormValueNormalizer::priceToCents(
                $request->request->get('price', null !== $product?->getPriceCents() ? $product->getPriceCents() / 100 : 0),
            );
            if ($priceCents < 0) {
                throw new ProductFormRequestException('Le prix doit être positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $category = $this->resolveCategory((int) $request->request->get(
                'categoryId',
                $product?->getCategory()->getId() ?? 0,
            ));
            $brand = $this->resolveBrand(ProductFormValueNormalizer::optionalInt(
                $request->request->get('brandId', $product?->getBrandId()),
            ));
            $discount = $this->discount->map($request);

            return new ProductWriteData(
                trim((string) $request->request->get('name', $product?->getName() ?? '')),
                strtoupper(trim((string) $request->request->get('sku', $product?->getSku() ?? ''))),
                ProductFormValueNormalizer::optionalString($request->request->get('slug', $product?->getSlug())),
                (string) $request->request->get('description', $product?->getDescription() ?? ''),
                ProductFormValueNormalizer::optionalString($request->request->get('shortDescription', $product?->getShortDescription())),
                $priceCents,
                (int) $request->request->get('stock', $product?->getStock() ?? 0),
                ProductFormValueNormalizer::boolean($request->request->get('isPublished', $product?->isPublished() ?? '1')),
                ProductFormValueNormalizer::boolean($request->request->get('isFeaturedHome', $product?->isFeaturedHome() ?? false)),
                $category,
                $this->gallery->files($request),
                ProductFormValueNormalizer::optionalString($request->request->get('imageAlt', $product?->getImageAlt())),
                null === $product ? [] : $this->gallery->removals($request),
                null !== $product && ProductFormValueNormalizer::boolean($request->request->get('removeImage', false)),
                (string) $request->request->get('sellingType', $product?->getSellingType() ?? 'sale'),
                $brand,
                ProductFormValueNormalizer::optionalString($request->request->get('variantGroup', $product?->getVariantGroup())),
                ProductFormValueNormalizer::optionalInt($request->request->get('releaseYear', $product?->getReleaseYear())),
                ProductFormValueNormalizer::optionalString($request->request->get('storageCapacity', $product?->getStorageCapacity())),
                ProductFormValueNormalizer::optionalString($request->request->get('memoryRam', $product?->getMemoryRam())),
                ProductFormValueNormalizer::optionalString($request->request->get('color', $product?->getColor())),
                $this->variants->parse($request->request->get('variants')),
                $discount['enabled'],
                $discount['type'],
                $discount['value'],
                $discount['startsAt'],
                $discount['endsAt'],
            );
        } catch (ProductFormRequestException $exception) {
            throw $exception;
        } catch (\InvalidArgumentException $exception) {
            throw new ProductFormRequestException($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function resolveCategory(int $categoryId): Category
    {
        $category = $this->categories->find($categoryId);
        if (!$category instanceof Category) {
            throw new ProductFormRequestException('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $category;
    }

    private function resolveBrand(?int $brandId): ?Brand
    {
        if (null === $brandId) {
            return null;
        }

        $brand = $this->brands->find($brandId);
        if (!$brand instanceof Brand) {
            throw new ProductFormRequestException('Marque introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $brand;
    }
}
