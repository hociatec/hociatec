import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type { DeleteDto, QuoteDto } from '../types/quoteTypes';
import { extractQuoteApiError, unwrapQuoteApiData, unwrapQuoteApiResult } from './quoteApiShared';
import { parseQuote } from '../quoteValidation';

export const fetchMyQuotes = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<QuoteDto>> => {
  const data = unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<{ items: QuoteDto[]; meta: PaginationMeta }>>(
        '/api/quotes/me',
        { params: { page, perPage } },
      )
    ).data,
  );

  return { items: data.items.map(parseQuote), meta: data.meta };
};
export const fetchMyQuote = async (id: number) =>
  parseQuote(
    unwrapQuoteApiData((await httpClient.get<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}`)).data),
  );
export const generateMyQuotePdf = async (id: number) =>
  (
    await httpClient.post(`/api/quotes/me/${id}/pdf`, null, {
      responseType: 'blob',
    })
  ).data as Blob;
export const deleteMyQuote = async (id: number) =>
  unwrapQuoteApiResult(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/quotes/me/${id}`)).data,
  );

export const acceptMyQuote = async (id: number) => {
  try {
    const result = unwrapQuoteApiResult(
      (await httpClient.post<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}/accept`)).data,
    );

    return { ...result, data: parseQuote(result.data) };
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Impossible d’accepter le devis.'));
  }
};
export const refuseMyQuote = async (id: number) => {
  try {
    const result = unwrapQuoteApiResult(
      (await httpClient.post<ApiResponse<QuoteDto>>(`/api/quotes/me/${id}/refuse`)).data,
    );

    return { ...result, data: parseQuote(result.data) };
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Impossible de refuser le devis.'));
  }
};
