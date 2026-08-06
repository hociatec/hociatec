import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import type { ApiMutationResult, ApiResponse } from '@/shared/types/api';
import type { CatalogProduct, CatalogSort, UpsertProductPayload } from './apiTypes';
import { parseCatalogProduct, parseCatalogSearchMeta } from './catalogValidation';

export interface AdminProductsPageMeta {
  page: number;
  perPage: number;
  total: number;
  totalPages: number;
}

export interface AdminProductsPageParams {
  page: number;
  perPage: number;
  search?: string;
  category?: string;
  featured?: boolean;
  sellingType?: 'sale' | 'rental';
  minPrice?: number;
  maxPrice?: number;
  stock?: 'low';
  sort?: CatalogSort;
}

export const fetchAdminProducts = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogProduct[] }>>(
    '/api/admin/catalog/products',
  );

  return unwrapApiData(data, 'Impossible de récupérer les produits.').items.map(parseCatalogProduct);
};

export const fetchAdminProductsPage = async (params: AdminProductsPageParams) => {
  const { data } = await httpClient.get<
    ApiResponse<{ items: CatalogProduct[]; meta: AdminProductsPageMeta }>
  >('/api/admin/catalog/products', { params });
  const payload = unwrapApiData(data, 'Impossible de récupérer les produits.');

  return { items: payload.items.map(parseCatalogProduct), meta: parseCatalogSearchMeta(payload.meta) };
};

export const fetchAdminProduct = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogProduct>>(
    `/api/admin/catalog/products/${id}`,
  );

  return parseCatalogProduct(unwrapApiData(data, 'Produit introuvable.'));
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
  if (payload.brandId !== undefined && payload.brandId !== null) {
    formData.set('brandId', payload.brandId.toString());
  } else if (payload.brandId === null) {
    formData.set('brandId', '');
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
  );
  const responseData = unwrapApiData(data, 'Creation du produit impossible.');

  return parseCatalogProduct(responseData);
};

export const updateProduct = async (id: number, payload: UpsertProductPayload) => {
  const formData = buildProductFormData(payload);
  formData.set('_method', 'PUT');

  const { data } = await httpClient.post<ApiResponse<CatalogProduct>>(
    `/api/admin/catalog/products/${id}`,
    formData,
  );
  const responseData = unwrapApiData(data, 'Mise à jour du produit impossible.');

  return parseCatalogProduct(responseData);
};

export const deleteProduct = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/products/${id}`,
  );
  const responseData = unwrapApiData(data, 'Suppression du produit impossible.');

  return {
    data: responseData,
    message: data.message,
  } satisfies ApiMutationResult<{ id: number }>;
};
