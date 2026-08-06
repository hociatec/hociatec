import { isAxiosError } from 'axios';

import { getHttpErrorMessage, httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse } from '@/shared/types/api';
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
import {
  parseCatalogCategory,
  parseCatalogProduct,
  parseCatalogProductsPayload,
  parseCatalogSearchPayload,
  parseCategoryWithProducts,
} from './catalogValidation';

type RequestOptions = {
  signal?: AbortSignal;
};

type PublicProductSearchParams = {
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
} & RequestOptions;

const hasValidNumber = (value: number | undefined) =>
  value !== undefined && !Number.isNaN(value);

const toPublicProductQueryParams = (params: PublicProductSearchParams) =>
  Object.fromEntries(
    [
      ['category', params.category],
      ['q', params.q],
      ['homepage', params.homepage === undefined ? undefined : params.homepage ? '1' : '0'],
      ['sellingType', params.sellingType],
      ['brand', params.brand],
      ['storageCapacity', params.storageCapacity],
      ['memoryRam', params.memoryRam],
      ['color', params.color],
      ['minPrice', hasValidNumber(params.minPrice) ? String(params.minPrice) : undefined],
      ['maxPrice', hasValidNumber(params.maxPrice) ? String(params.maxPrice) : undefined],
      ['inStock', params.inStock ? '1' : undefined],
      ['page', params.page === undefined ? undefined : String(params.page)],
      ['perPage', params.perPage === undefined ? undefined : String(params.perPage)],
      ['sort', params.sort],
    ].filter((entry): entry is [string, string] => typeof entry[1] === 'string' && entry[1] !== ''),
  );

export const fetchPublicCategories = async () => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
      '/api/public/catalog/categories',
    );

    const payload = unwrapApiData(data, 'Impossible de charger les catégories.');
    return payload.items.map(parseCatalogCategory);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les catégories.'));
  }
};

export const fetchPublicCategory = async (slug: string, options: RequestOptions = {}) => {
  try {
    const { data } = await httpClient.get<ApiResponse<CategoryWithProducts>>(
      `/api/public/catalog/categories/${slug}`,
      requestSignalConfig(options.signal),
    );
    const payload = unwrapApiData(data, 'Catégorie introuvable.');

    return parseCategoryWithProducts(payload);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Catégorie introuvable.'));
  }
};

export const fetchPublicProduct = async (slug: string, options: RequestOptions = {}) => {
  try {
    const { data } = await httpClient.get<ApiResponse<CatalogProduct>>(
      `/api/public/catalog/products/${slug}`,
      requestSignalConfig(options.signal),
    );
    const payload = unwrapApiData(data, 'Produit introuvable.');

    return parseCatalogProduct(payload);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Produit introuvable.'));
  }
};

export const shareProductByEmail = async (slug: string, payload: ShareProductEmailPayload) => {
  try {
    const { data } = await httpClient.post<
      ApiResponse<{ sent: boolean; to: string; message: string }>
    >(`/api/public/catalog/products/${slug}/share`, payload);

    return unwrapApiData(data, "Impossible d'envoyer le produit par e-mail.");
  } catch (error) {
    if (isAxiosError(error)) {
      throw new CatalogApiError(
        getHttpErrorMessage(error, "Impossible d'envoyer le produit par e-mail."),
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

    return unwrapApiData(data, 'Impossible de charger les avis.');
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les avis.'));
  }
};

export const fetchPublicProducts = async (
  params: PublicProductSearchParams = {},
) => {
  const queryParams = toPublicProductQueryParams(params);

  try {
    const { data } = await httpClient.get<ApiResponse<{ items: CatalogProduct[] }>>(
      '/api/public/catalog/products',
      { params: queryParams, ...requestSignalConfig(params.signal) },
    );
    const payload = unwrapApiData(data, 'Impossible de charger les produits.');

    return parseCatalogProductsPayload(payload).items;
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les produits.'));
  }
};

export const searchPublicProducts = async (
  params: PublicProductSearchParams = {},
) => {
  const queryParams = toPublicProductQueryParams(params);

  try {
    const { data } = await httpClient.get<
      ApiResponse<{ items: CatalogProduct[]; meta: CatalogSearchMeta; facets: CatalogSearchFacets }>
    >('/api/public/catalog/products', { params: queryParams, ...requestSignalConfig(params.signal) });
    const payload = unwrapApiData(data, 'Impossible de charger les produits.');

    return parseCatalogSearchPayload(payload);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de charger les produits.'));
  }
};
