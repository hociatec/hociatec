import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse } from '@/shared/types/api';
import {
  rethrowApiError,
  unwrap,
  type OperationsOverviewDto,
  type SupportRequestDto,
} from './operationsApiShared';

export const fetchOperationsOverview = async (): Promise<OperationsOverviewDto> => {
  const { data } = await httpClient.get<ApiResponse<OperationsOverviewDto>>(
    '/api/admin/operations/overview',
  );
  return unwrap(data, 'Impossible de charger l’exploitation admin');
};

export const fetchSupportRequests = async (): Promise<SupportRequestDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: SupportRequestDto[] }>>(
    '/api/admin/operations/support-requests',
  );
  return unwrap(data, 'Impossible de charger les demandes SAV').items ?? [];
};

export const createSupportRequest = async (payload: {
  customerId: number;
  orderId?: number | null;
  subject: string;
  reason: string;
  message?: string;
  internalNotes?: string;
}): Promise<SupportRequestDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: SupportRequestDto }>>(
    '/api/admin/operations/support-requests',
    payload,
  );
  return unwrap(data, 'Impossible de créer la demande SAV').item;
};

export const updateSupportRequest = async (
  id: number,
  payload: Partial<Pick<SupportRequestDto, 'status' | 'subject' | 'internalNotes'>>,
): Promise<SupportRequestDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ item: SupportRequestDto }>>(
    `/api/admin/operations/support-requests/${id}`,
    payload,
  );
  return unwrap(data, 'Impossible de mettre à jour la demande SAV').item;
};

export const replySupportRequest = async (
  id: number,
  payload: { subject: string; message: string; status?: string },
): Promise<SupportRequestDto> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ sent: boolean; item: SupportRequestDto }>>(
      `/api/admin/operations/support-requests/${id}/reply`,
      payload,
    );
    return unwrap(data, 'Impossible d’envoyer la réponse SAV').item;
  } catch (error) {
    return rethrowApiError(error, 'Impossible d’envoyer la réponse SAV');
  }
};
