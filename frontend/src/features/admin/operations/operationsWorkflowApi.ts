import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse } from '@/shared/types/api';
import type { OrderDto } from '@/features/orders/api';
import { rethrowApiError, unwrap, type EmailLogDto } from './operationsApiShared';

export const fetchEmailLogs = async (): Promise<EmailLogDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: EmailLogDto[] }>>(
    '/api/admin/operations/email-logs',
  );
  return unwrap(data, 'Impossible de charger les emails').items ?? [];
};

export const bulkUpdateOrderStatus = async (
  orderIds: number[],
  status: string,
): Promise<number> => {
  const { data } = await httpClient.post<ApiResponse<{ updated: number }>>(
    '/api/admin/operations/orders/bulk-status',
    { orderIds, status },
  );
  return unwrap(data, 'Impossible de modifier les commandes').updated;
};

export const convertQuoteToOrder = async (quoteReference: string): Promise<OrderDto> => {
  try {
    const reference = encodeURIComponent(quoteReference.trim());
    const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
      `/api/admin/operations/quotes/${reference}/convert-to-order`,
    );
    return unwrap(data, 'Impossible de convertir le devis').order;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de convertir le devis');
  }
};
