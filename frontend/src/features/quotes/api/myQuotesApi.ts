import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { DeleteDto, QuoteDto } from '../types/quoteTypes';
import { extractQuoteApiError, unwrapQuoteApiData } from './quoteApiShared';

export const fetchMyQuotes = async (): Promise<QuoteDto[]> =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<{ items: QuoteDto[] }>>('/api/quotes/me')).data,
  ).items;
export const fetchMyQuote = async (id: number) =>
  unwrapQuoteApiData((await httpClient.get<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}`)).data);
export const generateMyQuotePdf = async (id: number) =>
  (
    await httpClient.post(`/api/quotes/me/${id}/pdf`, null, {
      responseType: 'blob',
    })
  ).data as Blob;
export const deleteMyQuote = async (id: number): Promise<DeleteDto> =>
  unwrapQuoteApiData(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/quotes/me/${id}`)).data,
  );

export const acceptMyQuote = async (id: number) => {
  try {
    return unwrapQuoteApiData(
      (await httpClient.post<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}/accept`)).data,
    );
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Impossible d’accepter le devis.'));
  }
};
export const refuseMyQuote = async (id: number) => {
  try {
    return unwrapQuoteApiData(
      (await httpClient.post<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}/refuse`)).data,
    );
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Impossible de refuser le devis.'));
  }
};
