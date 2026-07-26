import { httpClient } from '@/shared/lib/httpClient';
import type { TradeInDto, TradeInInput, TradeInMetadataDto, TradeInStatus } from './types';

export async function fetchTradeInMetadata(): Promise<TradeInMetadataDto> {
  const response = await httpClient.get('/api/public/trade-ins/metadata');
  return response.data.data;
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
  const response = await httpClient.post(`/api${authenticated ? '' : '/public'}/trade-ins`, form);
  return { item: response.data.data, message: response.data.message };
}

export async function fetchMyTradeIns(): Promise<TradeInDto[]> {
  const response = await httpClient.get('/api/trade-ins/me');
  return response.data.data.items;
}

export async function respondToTradeIn(id: number, action: 'accept' | 'decline'): Promise<void> {
  await httpClient.post(`/api/trade-ins/${id}/respond/${action}`);
}

export async function adminFetchTradeIns(status?: TradeInStatus): Promise<TradeInDto[]> {
  const response = await httpClient.get('/api/admin/trade-ins', { params: status ? { status } : undefined });
  return response.data.data.items;
}

export async function adminFetchTradeIn(id: number): Promise<TradeInDto> {
  const response = await httpClient.get(`/api/admin/trade-ins/${id}`);
  return response.data.data.item;
}

export async function adminSetTradeInOffer(id: number, offerCents: number, adminNote: string): Promise<void> {
  await httpClient.put(`/api/admin/trade-ins/${id}/offer`, { offerCents, adminNote });
}

export async function adminSetTradeInStatus(id: number, status: TradeInStatus): Promise<void> {
  await httpClient.put(`/api/admin/trade-ins/${id}/status`, { status });
}

export async function adminDeleteTradeIn(id: number): Promise<void> {
  await httpClient.delete(`/api/admin/trade-ins/${id}`);
}

export async function adminDownloadTradeInDocument(id: number, document: 'rib' | 'receipt'): Promise<Blob> {
  return (await httpClient.get(`/api/admin/trade-ins/${id}/${document}`, { responseType: 'blob' })).data as Blob;
}

export async function downloadMyTradeInReceipt(id: number): Promise<Blob> {
  return (await httpClient.get(`/api/trade-ins/${id}/receipt`, { responseType: 'blob' })).data as Blob;
}

export async function adminCloseTradeIn(id: number, payload: { finalOfferCents: number; paymentMethod: string; paymentStatus: string; transactionReference?: string; note?: string }): Promise<void> {
  await httpClient.post(`/api/admin/trade-ins/${id}/close`, payload);
}
