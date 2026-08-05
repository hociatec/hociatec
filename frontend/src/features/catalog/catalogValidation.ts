import {
  ApiContractError,
  requireArray,
  requireBoolean,
  requireNumber,
  requireRecord,
  requireString,
  optionalNumber,
  optionalString,
} from '@/shared/lib/contractValidation';
import type {
  CatalogBrand,
  CatalogCategory,
  CatalogFacetCount,
  CatalogProduct,
  CatalogProductGalleryItem,
  CatalogSearchFacets,
  CatalogSearchMeta,
  CategoryWithProducts,
} from './apiTypes';

const SELLING_TYPES = new Set(['sale', 'rental']);
const DISCOUNT_TYPES = new Set(['percent', 'fixed_cents']);

export const parseCatalogCategory = (value: unknown): CatalogCategory => {
  const category = requireRecord(value);

  return {
    ...category,
    id: requireNumber(category.id),
    name: requireString(category.name),
    slug: requireString(category.slug),
    description: optionalString(category.description) ?? null,
    isVisible: requireBoolean(category.isVisible),
    createdAt: requireString(category.createdAt),
    updatedAt: requireString(category.updatedAt),
    productsCount: optionalNumber(category.productsCount) ?? undefined,
  } as CatalogCategory;
};

export const parseCatalogBrand = (value: unknown): CatalogBrand => {
  const brand = requireRecord(value);

  return {
    ...brand,
    id: requireNumber(brand.id),
    name: requireString(brand.name),
    createdAt: requireString(brand.createdAt),
    updatedAt: requireString(brand.updatedAt),
    productsCount: optionalNumber(brand.productsCount) ?? undefined,
  } as CatalogBrand;
};

const parseGalleryItem = (value: unknown): CatalogProductGalleryItem => {
  const item = requireRecord(value);

  return {
    position: requireNumber(item.position),
    url: requireString(item.url),
    alt: requireString(item.alt),
    isPrimary: requireBoolean(item.isPrimary),
  };
};

const parseProductCategorySummary = (value: unknown) => {
  const category = requireRecord(value);

  return {
    id: requireNumber(category.id),
    name: requireString(category.name),
    slug: requireString(category.slug),
  };
};

export const parseCatalogProduct = (value: unknown): CatalogProduct => {
  const product = requireRecord(value);
  const sellingType = requireString(product.sellingType);
  if (!SELLING_TYPES.has(sellingType)) throw new ApiContractError('Réponse produit invalide.');

  const discount =
    product.discount === null || product.discount === undefined
      ? null
      : (() => {
          const item = requireRecord(product.discount);
          const type = requireString(item.type);
          if (!DISCOUNT_TYPES.has(type)) throw new ApiContractError('Réponse produit invalide.');

          return {
            type: type as CatalogProduct['discount'] extends infer D
              ? D extends { type: infer T }
                ? T
                : never
              : never,
            value: requireNumber(item.value),
            startsAt: optionalString(item.startsAt) ?? null,
            endsAt: optionalString(item.endsAt) ?? null,
            active: requireBoolean(item.active),
          };
        })();

  return {
    ...product,
    id: requireNumber(product.id),
    name: requireString(product.name),
    slug: requireString(product.slug),
    sku: requireString(product.sku),
    shortDescription: optionalString(product.shortDescription) ?? null,
    description: requireString(product.description),
    priceCents: requireNumber(product.priceCents),
    sellingType: sellingType as CatalogProduct['sellingType'],
    sellingTypeLabel: requireString(product.sellingTypeLabel),
    priceUnitLabel: optionalString(product.priceUnitLabel) ?? null,
    brand: optionalString(product.brand) ?? undefined,
    brandId: optionalNumber(product.brandId) ?? undefined,
    variantGroup: optionalString(product.variantGroup) ?? undefined,
    variantPosition: optionalNumber(product.variantPosition) ?? undefined,
    variantsCount: optionalNumber(product.variantsCount) ?? undefined,
    totalStock: optionalNumber(product.totalStock) ?? undefined,
    variantColors:
      product.variantColors === undefined
        ? undefined
        : requireArray(product.variantColors).map((item) => requireString(item)),
    variantStorages:
      product.variantStorages === undefined
        ? undefined
        : requireArray(product.variantStorages).map((item) => requireString(item)),
    releaseYear: optionalNumber(product.releaseYear) ?? null,
    storageCapacity: optionalString(product.storageCapacity) ?? null,
    memoryRam: optionalString(product.memoryRam) ?? null,
    color: optionalString(product.color) ?? null,
    stock: requireNumber(product.stock),
    isPublished: requireBoolean(product.isPublished),
    isFeaturedHome: requireBoolean(product.isFeaturedHome),
    imageUrl: optionalString(product.imageUrl) ?? null,
    imageAlt: optionalString(product.imageAlt) ?? null,
    gallery: requireArray(product.gallery).map(parseGalleryItem),
    effectivePriceCents: optionalNumber(product.effectivePriceCents) ?? undefined,
    discount,
    createdAt: requireString(product.createdAt),
    updatedAt: requireString(product.updatedAt),
    category: parseProductCategorySummary(product.category),
    imageName: optionalString(product.imageName) ?? undefined,
    imageSize: optionalNumber(product.imageSize) ?? undefined,
  } as CatalogProduct;
};

const parseFacetCount = (value: unknown): CatalogFacetCount => {
  const facet = requireRecord(value);

  return {
    value: requireString(facet.value),
    count: requireNumber(facet.count),
    extra: optionalString(facet.extra) ?? null,
  };
};

export const parseCatalogSearchMeta = (value: unknown): CatalogSearchMeta => {
  const meta = requireRecord(value);

  return {
    page: requireNumber(meta.page),
    perPage: requireNumber(meta.perPage),
    total: requireNumber(meta.total),
    totalPages: requireNumber(meta.totalPages),
  };
};

export const parseCatalogSearchFacets = (value: unknown): CatalogSearchFacets => {
  const facets = requireRecord(value);
  const price = requireRecord(facets.price);

  return {
    brands: requireArray(facets.brands).map(parseFacetCount),
    categories: requireArray(facets.categories).map(parseFacetCount),
    storageCapacities: requireArray(facets.storageCapacities).map(parseFacetCount),
    memoryRams: requireArray(facets.memoryRams).map(parseFacetCount),
    colors: requireArray(facets.colors).map(parseFacetCount),
    price: {
      min: optionalNumber(price.min) ?? null,
      max: optionalNumber(price.max) ?? null,
    },
  };
};

export const parseCategoryWithProducts = (value: unknown): CategoryWithProducts => {
  const payload = requireRecord(value);

  return {
    category: parseCatalogCategory(payload.category),
    products: requireArray(payload.products).map(parseCatalogProduct),
  };
};

export const parseCatalogProductsPayload = (value: unknown) => {
  const payload = requireRecord(value);

  return {
    items: requireArray(payload.items).map(parseCatalogProduct),
  };
};

export const parseCatalogSearchPayload = (value: unknown) => {
  const payload = requireRecord(value);

  return {
    items: requireArray(payload.items).map(parseCatalogProduct),
    meta: parseCatalogSearchMeta(payload.meta),
    facets: parseCatalogSearchFacets(payload.facets),
  };
};
