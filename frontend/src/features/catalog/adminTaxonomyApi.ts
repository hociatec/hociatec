import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import { extractErrorMessage } from './apiShared';
import type {
  CatalogBrand,
  CatalogCategory,
  UpsertBrandPayload,
  UpsertCategoryPayload,
} from './apiTypes';

export const fetchAdminCategories = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogCategory[] }>>(
    '/api/admin/catalog/categories',
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les catégories.'));
};

export const fetchAdminCategory = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Catégorie introuvable.'));
};

export const createCategory = async (payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogCategory>>(
    '/api/admin/catalog/categories',
    payload,
  );

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Création de catégorie impossible.'));
};

export const updateCategory = async (id: number, payload: UpsertCategoryPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogCategory>>(
    `/api/admin/catalog/categories/${id}`,
    payload,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Mise à jour de la catégorie impossible.'));
};

export const deleteCategory = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/catalog/categories/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Suppression de la catégorie impossible.'));
};

export const fetchAdminBrands = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: CatalogBrand[] }>>(
    '/api/admin/catalog/brands',
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de récupérer les marques.'));
};

export const fetchAdminBrand = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Marque introuvable.'));
};

export const createBrand = async (payload: UpsertBrandPayload) => {
  const { data } = await httpClient.post<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands',
    payload,
  );

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Création de la marque impossible.'));
};

export const updateBrand = async (id: number, payload: UpsertBrandPayload) => {
  const { data } = await httpClient.put<ApiResponse<CatalogBrand>>(
    '/api/admin/catalog/brands/' + id,
    payload,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Mise à jour de la marque impossible.'));
};

export const deleteBrand = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    '/api/admin/catalog/brands/' + id,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Suppression de la marque impossible.'));
};
