import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type {
  DeleteDto,
  QuoteDto,
  QuoteInput,
  QuoteServiceDto,
  QuoteServiceInput,
  QuoteStatus,
  QuoteToOrderDto,
  AdminQuoteEmailDto,
} from '../types/quoteTypes';
import { extractQuoteApiError, unwrapQuoteApiData, unwrapQuoteApiResult } from './quoteApiShared';
import {
  parseAdminQuoteEmail,
  parseQuote,
  parseQuoteService,
  parseQuoteToOrder,
} from '../quoteValidation';

export interface QuoteMetadataOption { value: string; label: string }
export interface AdminQuoteMetadataDto {
  statuses: QuoteMetadataOption[];
  serviceBillingModes: QuoteMetadataOption[];
}

export const fetchAdminQuoteMetadata = async (): Promise<AdminQuoteMetadataDto> =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<AdminQuoteMetadataDto>>('/api/admin/quotes/metadata')).data,
  );

export const fetchAdminQuotes = async (params?: {
  from?: string;
  page?: number;
  perPage?: number;
  q?: string;
  status?: string;
  to?: string;
}): Promise<PaginatedResult<QuoteDto>> => {
  const response = await httpClient.get<ApiResponse<{ items: QuoteDto[]; meta: PaginationMeta }>>('/api/admin/quotes', {
    params: { page: 1, perPage: 10, ...params },
  });
  const data = unwrapQuoteApiData(response.data);

  return { items: data.items.map(parseQuote), meta: data.meta };
};

export const fetchAdminQuote = async (id: number) =>
  parseQuote(
    unwrapQuoteApiData(
      (await httpClient.get<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}`)).data,
    ),
  );
export const createAdminQuote = async (payload: QuoteInput) =>
  parseQuote(
    unwrapQuoteApiData(
      (await httpClient.post<ApiResponse<QuoteDto>>('/api/admin/quotes', payload)).data,
    ),
  );
export const updateAdminQuote = async (id: number, payload: QuoteInput) =>
  parseQuote(
    unwrapQuoteApiData(
      (await httpClient.post<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}`, payload)).data,
    ),
  );
export const deleteAdminQuote = async (id: number) =>
  unwrapQuoteApiResult(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/admin/quotes/${id}`)).data,
  );
export const duplicateAdminQuote = async (id: number) => {
  const result = unwrapQuoteApiResult(
    (await httpClient.post<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}/duplicate`)).data,
  );

  return { ...result, data: parseQuote(result.data) };
};

export const generateAdminQuotePdf = async (id: number) =>
  (await httpClient.post(`/api/admin/quotes/${id}/pdf`, null, { responseType: 'blob' }))
    .data as Blob;
export const sendAdminQuoteEmail = async (id: number, to?: string) =>
  parseAdminQuoteEmail(
    unwrapQuoteApiData(
      (
        await httpClient.post<ApiResponse<AdminQuoteEmailDto>>(
          `/api/admin/quotes/${id}/send-email`,
          to ? { to } : {},
        )
      ).data,
    ),
  );

export const updateAdminQuoteStatus = async (id: number, status: QuoteStatus) => {
  try {
    const result = unwrapQuoteApiResult(
      (await httpClient.patch<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}/status`, { status }))
        .data,
    );

    return { ...result, data: parseQuote(result.data) };
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Mise à jour impossible.'));
  }
};

export const convertAdminQuoteToOrder = async (reference: string | number) => {
  const encodedReference = encodeURIComponent(String(reference).trim());
  try {
    return parseQuoteToOrder(
      unwrapQuoteApiData(
        (
          await httpClient.post<ApiResponse<QuoteToOrderDto>>(
            `/api/admin/operations/quotes/${encodedReference}/convert-to-order`,
          )
        ).data,
      ),
    );
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Conversion impossible.'));
  }
};

export const fetchAdminQuoteServices = async (): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>('/api/admin/services', { params: { page: 1, perPage: 100 } })).data,
  ).items.map(parseQuoteService);

export const fetchAdminQuoteServicesPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<QuoteServiceDto>> => {
  const data = unwrapQuoteApiData(
    (
      await httpClient.get<ApiResponse<{ items: QuoteServiceDto[]; meta: PaginationMeta }>>(
        '/api/admin/services',
        { params: { page, perPage } },
      )
    ).data,
  );

  return { items: data.items.map(parseQuoteService), meta: data.meta };
};
export const fetchAdminQuoteService = async (id: number) =>
  parseQuoteService(
    unwrapQuoteApiData(
      (await httpClient.get<ApiResponse<QuoteServiceDto>>(`/api/admin/services/${id}`)).data,
    ),
  );

const toServiceFormData = (payload: Partial<QuoteServiceInput>) => {
  const form = new FormData();
  for (const [key, value] of Object.entries(payload)) {
    if (value instanceof File) {
      form.append(key, value);
      continue;
    }
    if (typeof value === 'boolean') {
      form.append(key, value ? '1' : '0');
      continue;
    }
    if (value !== undefined && value !== null && value !== '') form.append(key, String(value));
  }
  return form;
};

export const createAdminQuoteService = async (payload: QuoteServiceInput) => {
  const result = unwrapQuoteApiResult(
    (
      await httpClient.post<ApiResponse<QuoteServiceDto>>(
        '/api/admin/services',
        toServiceFormData(payload),
      )
    ).data,
  );

  return { ...result, data: parseQuoteService(result.data) };
};
export const updateAdminQuoteService = async (id: number, payload: Partial<QuoteServiceInput>) => {
  const result = unwrapQuoteApiResult(
    (
      await httpClient.post<ApiResponse<QuoteServiceDto>>(
        `/api/admin/services/${id}`,
        toServiceFormData(payload),
      )
    ).data,
  );

  return { ...result, data: parseQuoteService(result.data) };
};
export const deleteAdminQuoteService = async (id: number) =>
  unwrapQuoteApiResult(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/admin/services/${id}`)).data,
  );
