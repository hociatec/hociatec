<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Catalog\Mapper;

use App\Module\Admin\Application\Catalog\DTO\ProductWriteData;
use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Module\Admin\Application\Catalog\Parser\ProductAttributePayloadParser;
use App\Module\Admin\Application\Catalog\Parser\ProductVariantPayloadParser;
use App\Module\Catalog\Application\DTO\ProductCoreWriteData;
use App\Module\Catalog\Application\DTO\ProductDiscountWriteData;
use App\Module\Catalog\Application\DTO\ProductGalleryWriteData;
use App\Module\Catalog\Application\DTO\ProductVariantWriteData;
use App\Module\Catalog\Application\Port\BrandRepositoryPort;
use App\Module\Catalog\Application\Port\CategoryRepositoryPort;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\LegacyProductAttribute;
use App\Module\Catalog\Domain\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProductFormRequestMapper
{
    private ProductAttributePayloadParser $attributes;
    private ProductVariantPayloadParser $variants;
    private ProductGalleryRequestMapper $gallery;
    private ProductDiscountRequestMapper $discount;

    public function __construct(
        private CategoryRepositoryPort $categories,
        private BrandRepositoryPort $brands,
        ProductAttributePayloadParser|ProductVariantPayloadParser $attributesOrVariants,
        ProductVariantPayloadParser|ProductGalleryRequestMapper $variantsOrGallery,
        ProductGalleryRequestMapper|ProductDiscountRequestMapper $galleryOrDiscount,
        ?ProductDiscountRequestMapper $discount = null,
    ) {
        if ($attributesOrVariants instanceof ProductAttributePayloadParser) {
            $this->attributes = $attributesOrVariants;
            $this->variants = $variantsOrGallery instanceof ProductVariantPayloadParser ? $variantsOrGallery : new ProductVariantPayloadParser($this->attributes);
            $this->gallery = $galleryOrDiscount instanceof ProductGalleryRequestMapper ? $galleryOrDiscount : new ProductGalleryRequestMapper();
            $this->discount = $discount ?? new ProductDiscountRequestMapper();

            return;
        }

        $this->attributes = new ProductAttributePayloadParser();
        $this->variants = $attributesOrVariants;
        $this->gallery = $variantsOrGallery instanceof ProductGalleryRequestMapper ? $variantsOrGallery : new ProductGalleryRequestMapper();
        $this->discount = $galleryOrDiscount instanceof ProductDiscountRequestMapper ? $galleryOrDiscount : ($discount ?? new ProductDiscountRequestMapper());
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
            $sellingType = ProductFormValueNormalizer::optionalString($request->request->get('sellingType', $product?->getSellingType()));
            $legacyPriceCents = ProductFormValueNormalizer::optionalPriceToCents(
                $request->request->get('price', null !== $product?->getSalePriceCents() ? $product->getSalePriceCents() / 100 : 0),
            );
            if (null !== $legacyPriceCents && $legacyPriceCents < 0) {
                throw new ProductFormRequestException('Le prix doit être positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $salePriceCents = ProductFormValueNormalizer::optionalPriceToCents(
                $request->request->get('salePrice', $legacyPriceCents ?? (null !== $product?->getSalePriceCents() ? $product->getSalePriceCents() / 100 : 0)),
            );
            $rentalPriceCents = ProductFormValueNormalizer::optionalPriceToCents(
                $request->request->get('rentalPrice', 'rental' === $sellingType ? $legacyPriceCents : (null !== $product?->getRentalPriceCents() ? $product->getRentalPriceCents() / 100 : null)),
            );
            if (null !== $salePriceCents && $salePriceCents < 0) {
                throw new ProductFormRequestException('Le prix de vente doit être positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if (null !== $rentalPriceCents && $rentalPriceCents < 0) {
                throw new ProductFormRequestException('Le prix mensuel de location doit être positif.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $availableForSale = ProductFormValueNormalizer::boolean($request->request->get('availableForSale', $product?->isAvailableForSale() ?? true));
            $availableForRental = ProductFormValueNormalizer::boolean($request->request->get('availableForRental', $product?->isAvailableForRental() ?? false));

            $category = $this->resolveCategory((int) $request->request->get(
                'categoryId',
                $product?->getCategory()->getId() ?? 0,
            ));
            $brand = $this->resolveBrand(ProductFormValueNormalizer::optionalInt(
                $request->request->get('brandId', $product?->getBrandId()),
            ));
            $discount = $this->discount->map($request);

            return new ProductWriteData(
                core: new ProductCoreWriteData([
                    'name' => trim((string) $request->request->get('name', $product?->getName() ?? '')),
                    'sku' => strtoupper(trim((string) $request->request->get('sku', $product?->getSku() ?? ''))),
                    'slug' => ProductFormValueNormalizer::optionalString($request->request->get('slug', $product?->getSlug())),
                    'description' => (string) $request->request->get('description', $product?->getDescription() ?? ''),
                    'shortDescription' => ProductFormValueNormalizer::optionalString($request->request->get('shortDescription', $product?->getShortDescription())),
                    'salePriceCents' => $salePriceCents,
                    'rentalPriceCents' => $rentalPriceCents,
                    'availableForSale' => $availableForSale,
                    'availableForRental' => $availableForRental,
                    'stock' => (int) $request->request->get('stock', $product?->getStock() ?? 0),
                    'isPublished' => ProductFormValueNormalizer::boolean($request->request->get('isPublished', $product?->isPublished() ?? '1')),
                    'isFeaturedHome' => ProductFormValueNormalizer::boolean($request->request->get('isFeaturedHome', $product?->isFeaturedHome() ?? false)),
                    'category' => $category,
                    'imageAlt' => ProductFormValueNormalizer::optionalString($request->request->get('imageAlt', $product?->getImageAlt())),
                    'priceCents' => $legacyPriceCents,
                    'sellingType' => $sellingType,
                    'brand' => $brand,
                ]),
                gallery: new ProductGalleryWriteData(
                    files: $this->gallery->files($request),
                    toRemove: null === $product ? [] : $this->gallery->removals($request),
                    removeMainImage: null !== $product && ProductFormValueNormalizer::boolean($request->request->get('removeImage', false)),
                ),
                variant: new ProductVariantWriteData(
                    group: ProductFormValueNormalizer::optionalString($request->request->get('variantGroup', $product?->getVariantGroup())),
                    releaseYear: ProductFormValueNormalizer::optionalInt($request->request->get('releaseYear', $product?->getReleaseYear())),
                    attributes: $this->resolveAttributes($request, $product),
                    definitions: $this->variants->parse($request->request->get('variants')),
                ),
                discount: new ProductDiscountWriteData(
                    enabled: $discount['enabled'],
                    type: $discount['type'],
                    value: $discount['value'],
                    startsAt: $discount['startsAt'],
                    endsAt: $discount['endsAt'],
                ),
            );
        } catch (ProductFormRequestException $exception) {
            throw $exception;
        } catch (\InvalidArgumentException $exception) {
            throw ProductFormRequestException::fromInvalidArgument($exception, Response::HTTP_BAD_REQUEST);
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

    /**
     * @return list<array{code:string,label:string,value:string}>
     */
    private function resolveAttributes(Request $request, ?Product $product): array
    {
        $parsed = $this->attributes->parse($request->request->get('attributes'));

        if ([] !== $parsed) {
            return $parsed;
        }

        $legacy = [];

        $storage = ProductFormValueNormalizer::optionalString($request->request->get('storageCapacity', $product?->getStorageCapacity()));
        $storageAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::STORAGE_CODE, $storage);
        if (null !== $storageAttribute) {
            $legacy[] = $storageAttribute;
        }

        $memoryRam = ProductFormValueNormalizer::optionalString($request->request->get('memoryRam', $product?->getMemoryRam()));
        $memoryRamAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::MEMORY_RAM_CODE, $memoryRam);
        if (null !== $memoryRamAttribute) {
            $legacy[] = $memoryRamAttribute;
        }

        $color = ProductFormValueNormalizer::optionalString($request->request->get('color', $product?->getColor()));
        $colorAttribute = LegacyProductAttribute::fromValue(LegacyProductAttribute::COLOR_CODE, $color);
        if (null !== $colorAttribute) {
            $legacy[] = $colorAttribute;
        }

        if ([] !== $legacy) {
            return $legacy;
        }

        return $product?->getAttributes() ?? [];
    }
}
