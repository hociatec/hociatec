import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '../types/quoteTypes';
import { unwrapQuoteApiResult, unwrapQuoteApiData } from './quoteApiShared';
import { parseQuote, parseQuoteService } from '../quoteValidation';

type RequestOptions = {
  signal?: AbortSignal;
};

export const createPublicQuote = async (payload: QuoteInput) => {
  const result = unwrapQuoteApiResult(
    (await httpClient.post<ApiResponse<QuoteDto>>('/api/public/quotes', payload)).data,
  );

  return { ...result, data: parseQuote(result.data) };
};
export const fetchPublicQuoteServices = async (
  options: RequestOptions = {},
): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>('/api/public/services', {
        signal: options.signal,
      })
    ).data,
  ).items.map(parseQuoteService);
export const fetchPublicQuoteService = async (id: number, options: RequestOptions = {}) =>
  parseQuoteService(
    unwrapQuoteApiData(
      (
        await httpClient.get<ApiResponse<QuoteServiceDto>>(`/api/public/services/${id}`, {
          signal: options.signal,
        })
      ).data,
    ),
  );
