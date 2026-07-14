import { httpClient } from '../../shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '../../shared/types/api';

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
  brand?: string | null;
  variantGroup?: string | null;
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

export interface ShareProductEmailPayload {
  email: string;
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

export interface UpsertProductPayload {
  name: string;
  sku: string;
  slug?: string | null;
  description: string;
  shortDescription?: string | null;
  price: number;
  sellingType?: 'sale' | 'rental';
  brand?: string | null;
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
  // discounts
  discountEnabled?: boolean;
  discountType?: 'percent' | 'fixed';
  discountValue?: number; // percent or euros depending on type
  discountStartsAt?: string | null; // ISO or yyyy-mm-dd
  discountEndsAt?: string | null;
}

const extractErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error' ? response.message : fallback;

export const fetchPublicCategories = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
    '/api/public/catalog/categories',
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de charger les categories.'));
};

export const fetchPublicCategory = async (slug: string) => {
  const { data } = await httpClient.get<ApiResponse<CategoryWithProducts>>(
    `/api/public/catalog/categories/${slug}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Categorie introuvable.'));
};

export const fetchPublicProduct = async (slug: string) => {
  const { data } = await httpClient.get<ApiResponse<CatalogProduct>>(
    `/api/public/catalog/products/${slug}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Produit introuvable.'));
};

export const shareProductByEmail = async (slug: string, payload: ShareProductEmailPayload) => {
  const { data } = await httpClient.post<ApiResponse<{ sent: boolean; to: string; message: string }>>(
    `/api/public/catalog/products/${slug}/share`,
    payload,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, "Impossible d'envoyer le produit par e-mail."));
};

export const fetchProductReviews = async (
  slug: string,
  params: { page?: number; perPage?: number } = {},
) => {
  const { data } = await httpClient.get<
    ApiResponse<{ items: ProductPublicReview[]; meta: { page: number; perPage: number; total: number; average: number } }>
  >(`/api/public/catalog/products/${slug}/reviews`, {
    params: {
      page: params.page ?? 1,
      perPage: params.perPage ?? 10,
    },
  });

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de charger les avis.'));
};

export const fetchPublicProducts = async (params: {
  category?: string;
  q?: string;
  homepage?: boolean;
  sellingType?: 'sale' | 'rental';
  brand?: string;
  storageCapacity?: string;
  memoryRam?: string;
  color?: string;
  sort?: 'price_asc' | 'price_desc' | 'release_year_desc' | 'release_year_asc';
} = {}) => {
  const queryParams: Record<string, string> = {};

  if (params.category) {
    queryParams.category = params.category;
  }

  if (params.q) {
    queryParams.q = params.q;
  }

  if (params.homepage !== undefined) {
    queryParams.homepage = params.homepage ? '1' : '0';
  }

  if (params.sellingType) {
    queryParams.sellingType = params.sellingType;
  }

  if (params.brand) {
    queryParams.brand = params.brand;
  }

  if (params.storageCapacity) {
    queryParams.storageCapacity = params.storageCapacity;
  }

  if (params.memoryRam) {
    queryParams.memoryRam = params.memoryRam;
  }

  if (params.color) {
    queryParams.color = params.color;
  }

  if (params.sort) {
    queryParams.sort = params.sort;
  }

  const { data } = await httpClient.get<ApiResponse<{ items: CatalogProduct[] }>>(
    '/api/public/catalog/products',
    { params: queryParams },
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de charger les produits.'));
};

export const fetchAdminCategories = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
    '/api/admin/catalog/categories',
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de recuperer les categories.'));
};

export const fetchAdminCategory = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Categorie introuvable.'));
};

export const createCategory = async (payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogCategory>>(
    '/api/admin/catalog/categories',
    payload,
  );

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Creation de categorie impossible.'));
};

export const updateCategory = async (id: number, payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
    payload,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Mise a jour de la categorie impossible.'));
};

export const deleteCategory = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Suppression de la categorie impossible.'));
};

export const fetchAdminProducts = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogProduct[] }>>(
    '/api/admin/catalog/products',
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de recuperer les produits.'));
};

export const fetchAdminProduct = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogProduct>>(
    `/api/admin/catalog/products/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Produit introuvable.'));
};

const buildProductFormData = (payload: UpsertProductPayload) => {
  const formData = new FormData();
  formData.set('name', payload.name);
  formData.set('sku', payload.sku);
  if (payload.slug !== undefined) {
    formData.set('slug', payload.slug ?? '');
  }
  formData.set('description', payload.description);
  if (payload.shortDescription !== undefined) {
    formData.set('shortDescription', payload.shortDescription ?? '');
  }
  formData.set('price', payload.price.toString());
  if (payload.sellingType) {
    formData.set('sellingType', payload.sellingType);
  }
  if (payload.brand !== undefined) {
    formData.set('brand', payload.brand ?? '');
  }
  if (payload.variantGroup !== undefined) {
    formData.set('variantGroup', payload.variantGroup ?? '');
  }
  if (payload.releaseYear !== undefined && payload.releaseYear !== null) {
    formData.set('releaseYear', payload.releaseYear.toString());
  } else if (payload.releaseYear === null) {
    formData.set('releaseYear', '');
  }
  if (payload.storageCapacity !== undefined) {
    formData.set('storageCapacity', payload.storageCapacity ?? '');
  }
  if (payload.memoryRam !== undefined) {
    formData.set('memoryRam', payload.memoryRam ?? '');
  }
  if (payload.color !== undefined) {
    formData.set('color', payload.color ?? '');
  }
  if (payload.variants && payload.variants.length > 0) {
    formData.set('variants', JSON.stringify(payload.variants));
  }
  formData.set('stock', payload.stock.toString());
  formData.set('isPublished', payload.isPublished ? '1' : '0');
  formData.set('isFeaturedHome', payload.isFeaturedHome ? '1' : '0');
  formData.set('categoryId', payload.categoryId.toString());
  if (payload.imageAlt !== undefined) {
    formData.set('imageAlt', payload.imageAlt ?? '');
  }
  if (payload.removeImage) {
    formData.set('removeImage', '1');
  }
  if (payload.removeGallery?.length) {
    Array.from(new Set(payload.removeGallery))
      .filter((index) => typeof index === 'number' && !Number.isNaN(index))
      .forEach((index) => formData.append('removeGallery[]', index.toString()));
  }

  // discounts
  if (payload.discountEnabled !== undefined) {
    formData.set('discountEnabled', payload.discountEnabled ? '1' : '0');
  }
  if (payload.discountType) {
    formData.set('discountType', payload.discountType);
  }
  if (payload.discountValue !== undefined) {
    formData.set('discountValue', String(payload.discountValue));
  }
  if (payload.discountStartsAt) {
    formData.set('discountStartsAt', payload.discountStartsAt);
  }
  if (payload.discountEndsAt) {
    formData.set('discountEndsAt', payload.discountEndsAt);
  }

  const galleryFiles: Array<File | null> = [null, null, null, null];

  if (payload.gallery) {
    payload.gallery.forEach((file, index) => {
      if (index < galleryFiles.length) {
        galleryFiles[index] = file ?? null;
      }
    });
  }

  if (payload.image instanceof File) {
    galleryFiles[0] = payload.image;
  }

  galleryFiles.forEach((file, index) => {
    if (file instanceof File) {
      formData.append(`gallery[${index}]`, file);
      if (index === 0) {
        formData.set('image', file);
      }
    }
  });

  return formData;
};

export const createProduct = async (payload: UpsertProductPayload) => {
  const formData = buildProductFormData(payload);

  const { data } = await httpClient.post<ApiResponse<CatalogProduct>>(
    '/api/admin/catalog/products',
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    },
  );

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Creation du produit impossible.'));
};

export const updateProduct = async (id: number, payload: UpsertProductPayload) => {
  const formData = buildProductFormData(payload);
  formData.set('_method', 'PUT');

  const { data } = await httpClient.post<ApiResponse<CatalogProduct>>(
    `/api/admin/catalog/products/${id}`,
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    },
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Mise a jour du produit impossible.'));
};

export const deleteProduct = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/products/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Suppression du produit impossible.'));
};
