import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';

export interface SupportTimelineEntryDto {
  id: string;
  type: string;
  actor: string;
  visibility: string;
  authorLabel: string;
  subject?: string | null;
  message?: string | null;
  status?: string | null;
  statusLabel?: string | null;
  attachments?: Array<{
    name: string;
    originalName: string;
    contentType: string;
    size: number;
    uploadedAt: string;
  }>;
  createdAt: string;
}

export interface MySupportRequestDto {
  id: number;
  status: string;
  statusLabel: string;
  reason: string;
  subject: string;
  message?: string | null;
  customer: { id: number; name: string; email: string };
  order?: { id: number | null; number: string | null } | null;
  attachments: Array<{
    name: string;
    originalName: string;
    contentType: string;
    size: number;
    uploadedAt: string;
  }>;
  awaitingReplyFrom?: 'admin' | 'customer' | null;
  awaitingReplyLabel?: string | null;
  timeline: SupportTimelineEntryDto[];
  createdAt: string;
  updatedAt: string;
  resolvedAt?: string | null;
}

export const fetchMySupportRequests = async (page = 1, perPage = 10): Promise<PaginatedResult<MySupportRequestDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: MySupportRequestDto[]; meta: PaginationMeta }>>(
    '/api/support/me',
    { params: { page, perPage } },
  );
  const payload = unwrapApiData(data, 'Impossible de charger vos demandes SAV');

  return { items: payload.items, meta: payload.meta };
};

export const fetchMySupportRequestById = async (supportId: number): Promise<MySupportRequestDto> => {
  const { data } = await httpClient.get<ApiResponse<{ item: MySupportRequestDto }>>(`/api/support/me/${supportId}`);
  return unwrapApiData(data, 'Impossible de charger cette demande SAV').item;
};

export const createMySupportRequest = async (payload: {
  subject: string;
  reason: string;
  message: string;
  orderId?: number | null;
  attachments?: File[];
}): Promise<MySupportRequestDto> => {
  const formData = new FormData();
  formData.set('subject', payload.subject);
  formData.set('reason', payload.reason);
  formData.set('message', payload.message);
  if (payload.orderId) {
    formData.set('orderId', String(payload.orderId));
  }
  for (const file of payload.attachments ?? []) {
    formData.append('attachments', file);
  }

  const { data } = await httpClient.post<ApiResponse<{ item: MySupportRequestDto }>>('/api/support/me', formData);
  return unwrapApiData(data, 'Impossible de créer votre demande SAV').item;
};

export const replyMySupportRequest = async (
  supportId: number,
  payload: { subject?: string | null; message: string; attachments?: File[] },
): Promise<MySupportRequestDto> => {
  const formData = new FormData();
  formData.set('message', payload.message);
  if (payload.subject) {
    formData.set('subject', payload.subject);
  }
  for (const file of payload.attachments ?? []) {
    formData.append('attachments', file);
  }

  const { data } = await httpClient.post<ApiResponse<{ item: MySupportRequestDto }>>(
    `/api/support/me/${supportId}/reply`,
    formData,
  );
  return unwrapApiData(data, 'Impossible d’envoyer votre réponse SAV').item;
};
