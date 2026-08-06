import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type {
  ApiMutationResult,
  ApiResponse,
  PaginatedResult,
  PaginationMeta,
} from '@/shared/types/api';
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

  return unwrapApiData(data, 'Impossible de récupérer les catégories.').items.map(parseCatalogCategory);
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
  const payload = unwrapApiData(data, 'Impossible de récupérer les catégories.');

  return { items: payload.items.map(parseCatalogCategory), meta: payload.meta };
};

export const fetchAdminCategory = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
  );

  return parseCatalogCategory(unwrapApiData(data, 'Catégorie introuvable.'));
};

export const createCategory = async (payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogCategory>>(
    '/api/admin/catalog/categories',
    payload,
  );
  const responseData = unwrapApiData(data, 'Création de catégorie impossible.');

  return {
    data: parseCatalogCategory(responseData),
    message: data.message,
  } satisfies ApiMutationResult<CatalogCategory>;
};

export const updateCategory = async (id: number, payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
    payload,
  );
  const responseData = unwrapApiData(data, 'Mise à jour de la catégorie impossible.');

  return {
    data: parseCatalogCategory(responseData),
    message: data.message,
  } satisfies ApiMutationResult<CatalogCategory>;
};

export const deleteCategory = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/categories/${id}`,
  );
  const responseData = unwrapApiData(data, 'Suppression de la catégorie impossible.');

  return {
    data: responseData,
    message: data.message,
  } satisfies ApiMutationResult<{ id: number }>;
};

export const fetchAdminBrands = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogBrand[] }>>(
    '/api/admin/catalog/brands',
    { params: { page: 1, perPage: 100 } },
  );

  return unwrapApiData(data, 'Impossible de récupérer les marques.').items.map(parseCatalogBrand);
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
  const payload = unwrapApiData(data, 'Impossible de récupérer les marques.');

  return { items: payload.items.map(parseCatalogBrand), meta: payload.meta };
};

export const fetchAdminBrand = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
  );

  return parseCatalogBrand(unwrapApiData(data, 'Marque introuvable.'));
};

export const createBrand = async (payload: UpsertBrandPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands',
    payload,
  );
  const responseData = unwrapApiData(data, 'Création de la marque impossible.');

  return {
    data: parseCatalogBrand(responseData),
    message: data.message,
  } satisfies ApiMutationResult<CatalogBrand>;
};

export const updateBrand = async (id: number, payload: UpsertBrandPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
    payload,
  );
  const responseData = unwrapApiData(data, 'Mise à jour de la marque impossible.');

  return {
    data: parseCatalogBrand(responseData),
    message: data.message,
  } satisfies ApiMutationResult<CatalogBrand>;
};

export const deleteBrand = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    '/api/admin/catalog/brands/' + id,
  );
  const responseData = unwrapApiData(data, 'Suppression de la marque impossible.');

  return {
    data: responseData,
    message: data.message,
  } satisfies ApiMutationResult<{ id: number }>;
};
