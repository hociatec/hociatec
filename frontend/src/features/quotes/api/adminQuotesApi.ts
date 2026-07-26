import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
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
import { extractQuoteApiError, unwrapQuoteApiData } from './quoteApiShared';

export const fetchAdminQuotes = async (params?: {
  q?: string;
  status?: string;
}): Promise<QuoteDto[]> => {
  const response = await httpClient.get<ApiResponse<{ items: QuoteDto[] }>>('/api/admin/quotes', {
    params,
  });
  return unwrapQuoteApiData(response.data).items;
};

export const fetchAdminQuote = async (id: number) =>
  unwrapQuoteApiData((await httpClient.get<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}`)).data);
export const createAdminQuote = async (payload: QuoteInput) =>
  unwrapQuoteApiData(
    (await httpClient.post<ApiResponse<QuoteDto>>('/api/admin/quotes', payload)).data,
  );
export const updateAdminQuote = async (id: number, payload: QuoteInput) =>
  unwrapQuoteApiData(
    (await httpClient.post<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}`, payload)).data,
  );
export const deleteAdminQuote = async (id: number): Promise<DeleteDto> =>
  unwrapQuoteApiData(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/admin/quotes/${id}`)).data,
  );
export const duplicateAdminQuote = async (id: number) =>
  unwrapQuoteApiData(
    (await httpClient.post<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}/duplicate`)).data,
  );

export const generateAdminQuotePdf = async (id: number) =>
  (await httpClient.post(`/api/admin/quotes/${id}/pdf`, null, { responseType: 'blob' }))
    .data as Blob;
export const sendAdminQuoteEmail = async (id: number, to?: string) =>
  unwrapQuoteApiData(
    (
      await httpClient.post<ApiResponse<AdminQuoteEmailDto>>(
        `/api/admin/quotes/${id}/send-email`,
        to ? { to } : {},
      )
    ).data,
  );

export const updateAdminQuoteStatus = async (id: number, status: QuoteStatus) => {
  try {
    return unwrapQuoteApiData(
      (await httpClient.patch<ApiResponse<QuoteDto>>(`/api/admin/quotes/${id}/status`, { status }))
        .data,
    );
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Mise à jour impossible.'));
  }
};

export const convertAdminQuoteToOrder = async (reference: string | number) => {
  const encodedReference = encodeURIComponent(String(reference).trim());
  try {
    return unwrapQuoteApiData(
      (
        await httpClient.post<ApiResponse<QuoteToOrderDto>>(
          `/api/admin/operations/quotes/${encodedReference}/convert-to-order`,
        )
      ).data,
    );
  } catch (error) {
    throw new Error(extractQuoteApiError(error, 'Conversion impossible.'));
  }
};

export const fetchAdminQuoteServices = async (): Promise<QuoteServiceDto[]> =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<{ items: QuoteServiceDto[] }>>('/api/admin/services')).data,
  ).items;
export const fetchAdminQuoteService = async (id: number) =>
  unwrapQuoteApiData(
    (await httpClient.get<ApiResponse<QuoteServiceDto>>(`/api/admin/services/${id}`)).data,
  );

const toServiceFormData = (payload: Partial<QuoteServiceInput>) => {
  const form = new FormData();
  for (const [key, value] of Object.entries(payload))
    if (value !== undefined && value !== null && value !== '') form.append(key, String(value));
  return form;
};

export const createAdminQuoteService = async (payload: QuoteServiceInput) =>
  unwrapQuoteApiData(
    (
      await httpClient.post<ApiResponse<QuoteServiceDto>>(
        '/api/admin/services',
        toServiceFormData(payload),
        { headers: { 'Content-Type': 'multipart/form-data' } },
      )
    ).data,
  );
export const updateAdminQuoteService = async (id: number, payload: Partial<QuoteServiceInput>) =>
  unwrapQuoteApiData(
    (
      await httpClient.post<ApiResponse<QuoteServiceDto>>(
        `/api/admin/services/${id}`,
        toServiceFormData(payload),
        { headers: { 'Content-Type': 'multipart/form-data' } },
      )
    ).data,
  );
export const deleteAdminQuoteService = async (id: number): Promise<DeleteDto> =>
  unwrapQuoteApiData(
    (await httpClient.delete<ApiResponse<DeleteDto>>(`/api/admin/services/${id}`)).data,
  );
