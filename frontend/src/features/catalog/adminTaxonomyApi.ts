import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import { extractErrorMessage } from './apiShared';
import type {
  CatalogBrand,
  CatalogCategory,
  UpsertBrandPayload,
  UpsertCategoryPayload,
} from './apiTypes';
import { parseCatalogBrand, parseCatalogCategory } from './catalogValidation';

export const fetchAdminCategories = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
    '/api/admin/catalog/categories',
    { params: { page: 1, perPage: 100 } },
  );

  if (data.status === 'success') {
    return data.data.items.map(parseCatalogCategory);
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les catégories.'));
};

export const fetchAdminCategoriesPage = async (
  page = 1,
  perPage = 10,
  q = '',
): Promise<PaginatedResult<CatalogCategory>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[]; meta: PaginationMeta }>>(
    '/api/admin/catalog/categories',
    { params: { page, perPage, q: q || undefined } },
  );

  if (data.status === 'success') {
    return { items: data.data.items.map(parseCatalogCategory), meta: data.data.meta };
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les catégories.'));
};

export const fetchAdminCategory = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return parseCatalogCategory(data.data);
  }

  throw new Error(extractErrorMessage(data, 'Catégorie introuvable.'));
};

export const createCategory = async (payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogCategory>>(
    '/api/admin/catalog/categories',
    payload,
  );

  if (isApiOk(data)) {
    return { data: parseCatalogCategory(data.data), message: data.message } satisfies ApiMutationResult<CatalogCategory>;
  }

  throw new Error(extractErrorMessage(data, 'Création de catégorie impossible.'));
};

export const updateCategory = async (id: number, payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
    payload,
  );

  if (data.status === 'success') {
    return { data: parseCatalogCategory(data.data), message: data.message } satisfies ApiMutationResult<CatalogCategory>;
  }

  throw new Error(extractErrorMessage(data, 'Mise à jour de la catégorie impossible.'));
};

export const deleteCategory = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return { data: data.data, message: data.message } satisfies ApiMutationResult<{ id: number }>;
  }

  throw new Error(extractErrorMessage(data, 'Suppression de la catégorie impossible.'));
};

export const fetchAdminBrands = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogBrand[] }>>(
    '/api/admin/catalog/brands',
    { params: { page: 1, perPage: 100 } },
  );

  if (data.status === 'success') {
    return data.data.items.map(parseCatalogBrand);
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les marques.'));
};

export const fetchAdminBrandsPage = async (
  page = 1,
  perPage = 10,
  q = '',
): Promise<PaginatedResult<CatalogBrand>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogBrand[]; meta: PaginationMeta }>>(
    '/api/admin/catalog/brands',
    { params: { page, perPage, q: q || undefined } },
  );

  if (data.status === 'success') {
    return { items: data.data.items.map(parseCatalogBrand), meta: data.data.meta };
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les marques.'));
};

export const fetchAdminBrand = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
  );

  if (data.status === 'success') {
    return parseCatalogBrand(data.data);
  }

  throw new Error(extractErrorMessage(data, 'Marque introuvable.'));
};

export const createBrand = async (payload: UpsertBrandPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands',
    payload,
  );

  if (isApiOk(data)) {
    return { data: parseCatalogBrand(data.data), message: data.message } satisfies ApiMutationResult<CatalogBrand>;
  }

  throw new Error(extractErrorMessage(data, 'Création de la marque impossible.'));
};

export const updateBrand = async (id: number, payload: UpsertBrandPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
    payload,
  );

  if (data.status === 'success') {
    return { data: parseCatalogBrand(data.data), message: data.message } satisfies ApiMutationResult<CatalogBrand>;
  }

  throw new Error(extractErrorMessage(data, 'Mise à jour de la marque impossible.'));
};

export const deleteBrand = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    '/api/admin/catalog/brands/' + id,
  );

  if (data.status === 'success') {
    return { data: data.data, message: data.message } satisfies ApiMutationResult<{ id: number }>;
  }

  throw new Error(extractErrorMessage(data, 'Suppression de la marque impossible.'));
};
