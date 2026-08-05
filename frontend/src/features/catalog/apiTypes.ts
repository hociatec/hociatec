export interface CatalogCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  isVisible: boolean;
  createdAt: string;
  updatedAt: string;
  productsCount?: number;
}

export interface CatalogBrand {
  id: number;
  name: string;
  createdAt: string;
  updatedAt: string;
  productsCount?: number;
}

export interface CatalogProductGalleryItem {
  position: number;
  url: string;
  alt: string;
  isPrimary: boolean;
}

export interface CatalogProductGalleryMeta {
  position: number;
  name: string | null;
}

export interface CatalogProduct {
  id: number;
  name: string;
  slug: string;
  sku: string;
  shortDescription: string | null;
  description: string;
  priceCents: number;
  sellingType: 'sale' | 'rental';
  sellingTypeLabel: string;
  priceUnitLabel: string | null;
  brand?: string | null;
  brandId?: number | null;
  variantGroup?: string | null;
  variantPosition?: number;
  variantsCount?: number;
  totalStock?: number;
  variantColors?: string[];
  variantStorages?: string[];
  releaseYear?: number | null;
  storageCapacity?: string | null;
  memoryRam?: string | null;
  color?: string | null;
  stock: number;
  isPublished: boolean;
  isFeaturedHome: boolean;
  imageUrl: string | null;
  imageAlt: string | null;
  gallery: CatalogProductGalleryItem[];
  effectivePriceCents?: number;
  discount?: {
    type: 'percent' | 'fixed_cents';
    value: number;
    startsAt: string | null;
    endsAt: string | null;
    active: boolean;
  } | null;
  createdAt: string;
  updatedAt: string;
  category: {
    id: number;
    name: string;
    slug: string;
  };
  reviews?: {
    count: number;
    average: number;
  };
  imageName?: string | null;
  imageSize?: number | null;
  galleryMeta?: CatalogProductGalleryMeta[];
}

export interface ProductPublicReview {
  id: number;
  score: number;
  status: string;
  comment?: string | null;
  createdAt: string;
  publishedAt?: string | null;
  author: {
    id: number;
    displayName: string;
  };
}

export interface CatalogSearchMeta {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
}

export interface CatalogFacetCount {
  value: string;
  count: number;
  extra?: string | null;
}

export interface CatalogSearchFacets {
  brands: CatalogFacetCount[];
  categories: CatalogFacetCount[];
  storageCapacities: CatalogFacetCount[];
  memoryRams: CatalogFacetCount[];
  colors: CatalogFacetCount[];
  price: {
    min: number | null;
    max: number | null;
  };
}

export interface ShareProductEmailPayload {
  email: string;
}

export type CatalogSort =
  | 'relevance'
  | 'price_asc'
  | 'price_desc'
  | 'release_year_desc'
  | 'release_year_asc'
  | 'name_desc'
  | 'stock_desc'
  | 'stock_asc'
  | 'created_desc';

export class CatalogApiError extends Error {
  public readonly statusCode?: number | undefined;

  constructor(message: string, statusCode?: number) {
    super(message);
    this.name = 'CatalogApiError';
    this.statusCode = statusCode;
  }
}

export interface CategoryWithProducts {
  category: CatalogCategory;
  products: CatalogProduct[];
}

export interface UpsertCategoryPayload {
  name: string;
  slug?: string | null;
  description?: string | null;
  isVisible?: boolean;
}

export interface UpsertBrandPayload {
  name: string;
}

export interface UpsertProductPayload {
  name: string;
  sku: string;
  slug?: string | null;
  description: string;
  shortDescription?: string | null;
  price: number;
  sellingType?: 'sale' | 'rental';
  brandId?: number | null;
  variantGroup?: string | null;
  releaseYear?: number | null;
  storageCapacity?: string | null;
  memoryRam?: string | null;
  color?: string | null;
  stock: number;
  isPublished: boolean;
  isFeaturedHome: boolean;
  categoryId: number;
  image?: File | null;
  gallery?: Array<File | null>;
  imageAlt?: string | null;
  removeImage?: boolean;
  removeGallery?: number[];
  variants?: Array<{
    color?: string | null;
    storageCapacity?: string | null;
    stock: number;
  }>;
  discountEnabled?: boolean;
  discountType?: 'percent' | 'fixed';
  discountValue?: number;
  discountStartsAt?: string | null;
  discountEndsAt?: string | null;
}
