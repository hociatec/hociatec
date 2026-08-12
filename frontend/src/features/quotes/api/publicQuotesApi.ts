import { httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import { idempotencyRequestConfig } from '@/shared/lib/idempotency';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '../types/quoteTypes';
import { unwrapQuoteApiResult, unwrapQuoteApiData } from './quoteApiShared';
import { parseQuote, parseQuoteService } from '../quoteValidation';

type RequestOptions = {
  signal?: AbortSignal;
};

export const createPublicQuote = async (payload: QuoteInput) => {
  const result = unwrapQuoteApiResult(
    (
      await httpClient.post<ApiResponse<QuoteDto>>(
        '/api/public/quotes',
        payload,
        idempotencyRequestConfig('quote.public.create', payload),
      )
    ).data,
  );

  return { ...result, data: parseQuote(result.data) };
};
export const fetchPublicQuoteServices = async (
  options: RequestOptions = {},
): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>(
        '/api/public/services',
        requestSignalConfig(options.signal),
      )
    ).data,
  ).items.map(parseQuoteService);

export const searchPublicQuoteServices = async (
  params: {
    page?: number;
    perPage?: number;
    q?: string;
    signal?: AbortSignal;
  } = {},
): Promise<PaginatedResult<QuoteServiceDto>> => {
  const response = await httpClient.get<ApiResponse<{ items: QuoteServiceDto[]; meta: PaginationMeta }>>(
    '/api/public/services',
    {
      params: {
        page: params.page ?? 1,
        perPage: params.perPage ?? 20,
        q: params.q,
      },
      ...requestSignalConfig(params.signal),
    },
  );
  const data = unwrapQuoteApiData(response.data);

  return {
    items: data.items.map(parseQuoteService),
    meta: data.meta,
  };
};
export const fetchPublicQuoteService = async (id: number, options: RequestOptions = {}) =>
  parseQuoteService(
    unwrapQuoteApiData(
      (
        await httpClient.get<ApiResponse<QuoteServiceDto>>(
          `/api/public/services/${id}`,
          requestSignalConfig(options.signal),
        )
      ).data,
    ),
  );
