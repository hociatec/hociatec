import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import type {
  ApiMutationResult,
  ApiResponse,
  PaginatedResult,
  PaginationMeta,
} from '@/shared/types/api';
import type { TradeInDto, TradeInInput, TradeInMetadataDto, TradeInStatus } from './types';

export async function fetchTradeInMetadata(): Promise<TradeInMetadataDto> {
  const response = await httpClient.get<ApiResponse<TradeInMetadataDto>>('/api/public/trade-ins/metadata');
  return unwrapApiData(response.data, 'Impossible de charger les métadonnées des reprises.');
}

export async function createTradeIn(input: TradeInInput, authenticated: boolean): Promise<{ item: TradeInDto; message?: string }> {
  const form = new FormData();
  Object.entries(input).forEach(([key, value]) => {
    if ('rib' === key) {
      if (typeof File !== 'undefined' && value instanceof File) form.append(key, value);
    } else if (value !== null && value !== undefined) {
      form.append(key, String(value));
    }
  });
  const response = await httpClient.post<ApiResponse<{ item: TradeInDto }>>(
    `/api${authenticated ? '' : '/public'}/trade-ins`,
    form,
  );
  const payload = unwrapApiData(response.data, 'Impossible de créer la reprise.');
  return { item: payload.item, message: response.data.message };
}

export async function fetchMyTradeIns(page = 1, perPage = 10): Promise<PaginatedResult<TradeInDto>> {
  const response = await httpClient.get<ApiResponse<{ items: TradeInDto[]; meta: PaginationMeta }>>(
    '/api/trade-ins/me',
    { params: { page, perPage } },
  );
  return unwrapApiData(response.data, 'Impossible de charger vos reprises.');
}

export async function respondToTradeIn(id: number, action: 'accept' | 'decline'): Promise<void> {
  const response = await httpClient.post<ApiResponse<Record<string, unknown>>>(
    `/api/trade-ins/${id}/respond/${action}`,
  );
  unwrapApiData(response.data, 'Impossible de répondre à la reprise.');
}

export async function adminFetchTradeIns(
  status?: TradeInStatus,
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<TradeInDto>> {
  const response = await httpClient.get<ApiResponse<{ items: TradeInDto[]; meta: PaginationMeta }>>('/api/admin/trade-ins', {
    params: { page, perPage, ...(status ? { status } : {}) },
  });
  return unwrapApiData(response.data, 'Impossible de charger les reprises.');
}

export async function adminFetchTradeIn(id: number): Promise<TradeInDto> {
  const response = await httpClient.get<ApiResponse<{ item: TradeInDto }>>(`/api/admin/trade-ins/${id}`);
  return unwrapApiData(response.data, 'Reprise introuvable.').item;
}

export async function adminSetTradeInOffer(id: number, offerCents: number, adminNote: string): Promise<void> {
  const response = await httpClient.put<ApiResponse<Record<string, unknown>>>(
    `/api/admin/trade-ins/${id}/offer`,
    { offerCents, adminNote },
  );
  unwrapApiData(response.data, 'Impossible de proposer une offre.');
}

export async function adminSetTradeInStatus(id: number, status: TradeInStatus): Promise<void> {
  const response = await httpClient.put<ApiResponse<Record<string, unknown>>>(
    `/api/admin/trade-ins/${id}/status`,
    { status },
  );
  unwrapApiData(response.data, 'Impossible de changer le statut.');
}

export async function adminDeleteTradeIn(id: number): Promise<ApiMutationResult<unknown>> {
  const response = await httpClient.delete<ApiResponse<unknown>>(`/api/admin/trade-ins/${id}`);
  return {
    data: unwrapApiData(response.data, 'Impossible de supprimer la reprise.'),
    message: response.data.message,
  };
}

export async function adminDownloadTradeInDocument(id: number, document: 'rib' | 'receipt'): Promise<Blob> {
  return (await httpClient.get(`/api/admin/trade-ins/${id}/${document}`, { responseType: 'blob' })).data as Blob;
}

export async function downloadMyTradeInReceipt(id: number): Promise<Blob> {
  return (await httpClient.get(`/api/trade-ins/${id}/receipt`, { responseType: 'blob' })).data as Blob;
}

export async function adminCloseTradeIn(id: number, payload: { finalOfferCents: number; paymentMethod: string; paymentStatus: string; transactionReference?: string; note?: string }): Promise<ApiMutationResult<unknown>> {
  const response = await httpClient.post<ApiResponse<unknown>>(`/api/admin/trade-ins/${id}/close`, payload);
  return {
    data: unwrapApiData(response.data, 'Impossible de clôturer la reprise.'),
    message: response.data.message,
  };
}
