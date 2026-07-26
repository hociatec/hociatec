import { isAxiosError } from 'axios';

import { getHttpErrorMessage, httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import { extractErrorMessage } from './apiShared';
import type {
  CatalogCategory,
  CatalogProduct,
  CatalogSearchFacets,
  CatalogSearchMeta,
  CatalogSort,
  CategoryWithProducts,
  ProductPublicReview,
  ShareProductEmailPayload,
} from './apiTypes';
import { CatalogApiError } from './apiTypes';

export const fetchPublicCategories = async () => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
      '/api/public/catalog/categories',
    );

    if (data.status === 'success') {
      return data.data.items;
    }

    throw new Error(extractErrorMessage(data, 'Impossible de charger les catégories.'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les catégories.'));
  }
};

export const fetchPublicCategory = async (slug: string) => {
  try {
    const { data } = await httpClient.get<ApiResponse<CategoryWithProducts>>(
      `/api/public/catalog/categories/${slug}`,
    );

    if (data.status === 'success') {
      return data.data;
    }

    throw new Error(extractErrorMessage(data, 'Catégorie introuvable.'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Catégorie introuvable.'));
  }
};

export const fetchPublicProduct = async (slug: string) => {
  try {
    const { data } = await httpClient.get<ApiResponse<CatalogProduct>>(
      `/api/public/catalog/products/${slug}`,
    );

    if (data.status === 'success') {
      return data.data;
    }

    throw new Error(extractErrorMessage(data, 'Produit introuvable.'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Produit introuvable.'));
  }
};

export const shareProductByEmail = async (slug: string, payload: ShareProductEmailPayload) => {
  try {
    const { data } = await httpClient.post<
      ApiResponse<{ sent: boolean; to: string; message: string }>
    >(`/api/public/catalog/products/${slug}/share`, payload);

    if (data.status === 'success') {
      return data.data;
    }

    throw new CatalogApiError(
      extractErrorMessage(data, "Impossible d'envoyer le produit par e-mail."),
    );
  } catch (error) {
    if (isAxiosError(error)) {
      const response = error.response?.data as ApiResponse<unknown> | undefined;
      throw new CatalogApiError(
        response
          ? extractErrorMessage(response, "Impossible d'envoyer le produit par e-mail.")
          : "Impossible d'envoyer le produit par e-mail.",
        error.response?.status,
      );
    }

    throw error;
  }
};

export const fetchProductReviews = async (
  slug: string,
  params: { page?: number; perPage?: number } = {},
) => {
  try {
    const { data } = await httpClient.get<
      ApiResponse<{
        items: ProductPublicReview[];
        meta: { page: number; perPage: number; total: number; average: number };
      }>
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
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les avis.'));
  }
};

export const fetchPublicProducts = async (
  params: {
    category?: string;
    q?: string;
    homepage?: boolean;
    sellingType?: 'sale' | 'rental';
    brand?: string;
    storageCapacity?: string;
    memoryRam?: string;
    color?: string;
    minPrice?: number;
    maxPrice?: number;
    inStock?: boolean;
    page?: number;
    perPage?: number;
    sort?: CatalogSort;
  } = {},
) => {
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

  if (params.minPrice !== undefined && params.minPrice !== null && !Number.isNaN(params.minPrice)) {
    queryParams.minPrice = String(params.minPrice);
  }

  if (params.maxPrice !== undefined && params.maxPrice !== null && !Number.isNaN(params.maxPrice)) {
    queryParams.maxPrice = String(params.maxPrice);
  }

  if (params.inStock !== undefined) {
    queryParams.inStock = params.inStock ? '1' : '0';
  }

  if (params.page !== undefined) {
    queryParams.page = String(params.page);
  }

  if (params.perPage !== undefined) {
    queryParams.perPage = String(params.perPage);
  }

  if (params.sort) {
    queryParams.sort = params.sort;
  }

  try {
    const { data } = await httpClient.get<ApiResponse<{ items: CatalogProduct[] }>>(
      '/api/public/catalog/products',
      { params: queryParams },
    );

    if (data.status === 'success') {
      return data.data.items;
    }

    throw new Error(extractErrorMessage(data, 'Impossible de charger les produits.'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les produits.'));
  }
};

export const searchPublicProducts = async (
  params: {
    category?: string;
    q?: string;
    homepage?: boolean;
    sellingType?: 'sale' | 'rental';
    brand?: string;
    storageCapacity?: string;
    memoryRam?: string;
    color?: string;
    minPrice?: number;
    maxPrice?: number;
    inStock?: boolean;
    page?: number;
    perPage?: number;
    sort?: CatalogSort;
  } = {},
) => {
  const queryParams: Record<string, string> = {};

  if (params.category) queryParams.category = params.category;
  if (params.q) queryParams.q = params.q;
  if (params.homepage !== undefined) queryParams.homepage = params.homepage ? '1' : '0';
  if (params.sellingType) queryParams.sellingType = params.sellingType;
  if (params.brand) queryParams.brand = params.brand;
  if (params.storageCapacity) queryParams.storageCapacity = params.storageCapacity;
  if (params.memoryRam) queryParams.memoryRam = params.memoryRam;
  if (params.color) queryParams.color = params.color;
  if (params.minPrice !== undefined && params.minPrice !== null && !Number.isNaN(params.minPrice))
    queryParams.minPrice = String(params.minPrice);
  if (params.maxPrice !== undefined && params.maxPrice !== null && !Number.isNaN(params.maxPrice))
    queryParams.maxPrice = String(params.maxPrice);
  if (params.inStock !== undefined) queryParams.inStock = params.inStock ? '1' : '0';
  if (params.page !== undefined) queryParams.page = String(params.page);
  if (params.perPage !== undefined) queryParams.perPage = String(params.perPage);
  if (params.sort) queryParams.sort = params.sort;

  try {
    const { data } = await httpClient.get<
      ApiResponse<{ items: CatalogProduct[]; meta: CatalogSearchMeta; facets: CatalogSearchFacets }>
    >('/api/public/catalog/products', { params: queryParams });

    if (data.status === 'success') {
      return data.data;
    }

    throw new Error(extractErrorMessage(data, 'Impossible de charger les produits.'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les produits.'));
  }
};
