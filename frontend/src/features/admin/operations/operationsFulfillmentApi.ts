import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import {
  rethrowApiError,
  unwrap,
  type FulfillmentOrderDto,
} from './operationsApiShared';

export const fetchFulfillmentOrders = async (page = 1, perPage = 10): Promise<PaginatedResult<FulfillmentOrderDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FulfillmentOrderDto[]; meta: PaginationMeta }>>(
    '/api/admin/operations/fulfillment/orders',
    { params: { page, perPage } },
  );
  return unwrap(data, 'Impossible de charger les commandes à préparer');
};

export const shipFulfillmentOrder = async (
  id: number,
  payload: { carrier?: string; trackingNumber?: string; trackingUrl?: string },
): Promise<FulfillmentOrderDto> => {
  try {
    const { data } = await httpClient.patch<ApiResponse<{ order: FulfillmentOrderDto }>>(
      `/api/admin/operations/fulfillment/orders/${id}/ship`,
      payload,
    );
    return unwrap(data, 'Impossible de marquer la commande expédiée').order;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de marquer la commande expédiée');
  }
};
