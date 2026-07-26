import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { QuoteDto, QuoteInput, QuoteServiceDto } from '../types/quoteTypes';
import { unwrapQuoteApiData } from './quoteApiShared';

export const createPublicQuote = async (payload: QuoteInput) =>
  unwrapQuoteApiData(
    (await httpClient.post<ApiResponse<QuoteDto>>('/api/public/quotes', payload)).data,
  );
export const fetchPublicQuoteServices = async (): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>('/api/public/services')).data,
  ).items;
export const fetchPublicQuoteService = async (id: number) =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<QuoteServiceDto>>(`/api/public/services/${id}`)).data,
  );
