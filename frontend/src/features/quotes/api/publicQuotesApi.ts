import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '../types/quoteTypes';
import { unwrapQuoteApiResult, unwrapQuoteApiData } from './quoteApiShared';

type RequestOptions = {
  signal?: AbortSignal;
};

export const createPublicQuote = async (payload: QuoteInput) =>
  unwrapQuoteApiResult(
    (await httpClient.post<ApiResponse<QuoteDto>>('/api/public/quotes', payload)).data,
  );
export const fetchPublicQuoteServices = async (
  options: RequestOptions = {},
): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>('/api/public/services', {
        signal: options.signal,
      })
    ).data,
  ).items;
export const fetchPublicQuoteService = async (id: number, options: RequestOptions = {}) =>
  unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<QuoteServiceDto>>(`/api/public/services/${id}`, {
        signal: options.signal,
      })
    ).data,
  );
