import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

import type { Prestation, WorkingDay } from '@/features/appointments/publicApi';

const extractErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error' ? response.message : fallback;

export const fetchAdminPrestations = async () => {
  const { data } = await httpClient.get<ApiResponse<{ items: Prestation[] }>>(
    '/api/admin/appointments/prestations',
    { params: { page: 1, perPage: 100 } },
  );

  if (data.status === 'success') {
    return data.data.items;
  }

  throw new Error(extractErrorMessage(data, 'Erreur lors du chargement des prestations'));
};

export const fetchAdminPrestationsPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<Prestation>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: Prestation[]; meta: PaginationMeta }>>(
    '/api/admin/appointments/prestations',
    { params: { page, perPage } },
  );

  if (data.status === 'success') {
    return { items: data.data.items, meta: data.data.meta };
  }

  throw new Error(extractErrorMessage(data, 'Erreur lors du chargement des prestations'));
};

export const fetchAdminPrestation = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<Prestation>>(
    `/api/admin/appointments/prestations/${id}`,
  );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Prestation introuvable'));
};

export interface UpsertPrestationPayload {
  name: string;
  durationMinutes: number;
  price: number | string;
}

export const createPrestation = async (payload: UpsertPrestationPayload) => {
  const { data } = await httpClient.post<ApiResponse<Prestation>>(
    '/api/admin/appointments/prestations',
    payload,
  );

  if (isApiOk(data)) {
    return { data: data.data, message: data.message } satisfies ApiMutationResult<Prestation>;
  }

  throw new Error(data.message || 'Impossible de créer la prestation');
};

export const updatePrestation = async (id: number, payload: UpsertPrestationPayload) => {
  const { data } = await httpClient.put<ApiResponse<Prestation>>(
    `/api/admin/appointments/prestations/${id}`,
    payload,
  );

  if (data.status === 'success') {
    return { data: data.data, message: data.message } satisfies ApiMutationResult<Prestation>;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de mettre à jour la prestation'));
};

export const deletePrestation = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/appointments/prestations/${id}`,
  );

  if (data.status === 'success') {
    return { data: data.data, message: data.message } satisfies ApiMutationResult<{ id: number }>;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de supprimer la prestation'));
};

export const fetchConfiguration = async () => {
  const { data } = await httpClient.get<ApiResponse<{ days: WorkingDay[] }>>(
    '/api/admin/appointments/configuration',
  );

  if (data.status === 'success') {
    return data.data.days;
  }

  throw new Error(extractErrorMessage(data, 'Erreur lors du chargement de la configuration'));
};

export const updateConfiguration = async (days: WorkingDay[]) => {
  const { data } = await httpClient.put<ApiResponse<{ days: WorkingDay[] }>>(
    '/api/admin/appointments/configuration',
    { days },
  );

  if (data.status === 'success') {
    return data.data.days;
  }

  throw new Error(extractErrorMessage(data, 'Impossible de mettre à jour la configuration'));
};
