import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

import type { Prestation, WorkingDay } from '@/features/appointments/publicApi';

export const fetchAdminPrestationsPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<Prestation>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: Prestation[]; meta: PaginationMeta }>>(
    '/api/admin/appointments/prestations',
    { params: { page, perPage } },
  );
  return unwrapApiData(data, 'Erreur lors du chargement des prestations');
};

export const fetchAdminPrestation = async (id: number) => {
  const { data } = await httpClient.get<ApiResponse<Prestation>>(
    `/api/admin/appointments/prestations/${id}`,
  );

  return unwrapApiData(data, 'Prestation introuvable');
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

  const responsePayload = unwrapApiData(data, 'Impossible de créer la prestation');
  return { data: responsePayload, message: data.message };
};

export const updatePrestation = async (id: number, payload: UpsertPrestationPayload) => {
  const { data } = await httpClient.put<ApiResponse<Prestation>>(
    `/api/admin/appointments/prestations/${id}`,
    payload,
  );

  const responsePayload = unwrapApiData(data, 'Impossible de mettre à jour la prestation');
  return { data: responsePayload, message: data.message };
};

export const deletePrestation = async (id: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ id: number }>>(
    `/api/admin/appointments/prestations/${id}`,
  );

  const responsePayload = unwrapApiData(data, 'Impossible de supprimer la prestation');
  return { data: responsePayload, message: data.message };
};

export const fetchConfiguration = async () => {
  const { data } = await httpClient.get<ApiResponse<{ days: WorkingDay[] }>>(
    '/api/admin/appointments/configuration',
  );

  return unwrapApiData(data, 'Erreur lors du chargement de la configuration').days;
};

export const updateConfiguration = async (days: WorkingDay[]) => {
  const { data } = await httpClient.put<ApiResponse<{ days: WorkingDay[] }>>(
    '/api/admin/appointments/configuration',
    { days },
  );

  return unwrapApiData(data, 'Impossible de mettre à jour la configuration').days;
};
