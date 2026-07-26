import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse } from '@/shared/types/api';
import {
  rethrowApiError,
  unwrap,
  type FulfillmentOrderDto,
} from './operationsApiShared';

export const fetchFulfillmentOrders = async (): Promise<FulfillmentOrderDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FulfillmentOrderDto[] }>>(
    '/api/admin/operations/fulfillment/orders',
  );
  return unwrap(data, 'Impossible de charger les commandes à préparer').items ?? [];
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
