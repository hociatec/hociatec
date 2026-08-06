import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { OrderStatus, OrderStatusFilter } from '@/shared/contracts/statuses';
import type { AdminOrderMetadataDto, OrderDto, OrderEventDto, OrderProcessingDto } from './orderTypes';
import { parseOrder, parseOrderEvent, parseOrderProcessing } from './orderValidation';

export const fetchAdminOrderMetadata = async (): Promise<AdminOrderMetadataDto> => {
  const { data } = await httpClient.get<ApiResponse<AdminOrderMetadataDto>>('/api/admin/orders/metadata');
  if (isApiOk(data)) return data.data;
  throw new Error(data.message);
};

export const fetchAdminOrders = async (
  status: OrderStatusFilter = 'all',
  health: 'all' | 'issues' = 'all',
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<OrderDto>> => {
  const query = new URLSearchParams();
  query.set('page', String(page));
  query.set('perPage', String(perPage));
  if (status && status !== 'all') {
    query.set('status', status);
  }
  if (health === 'issues') {
    query.set('health', health);
  }

  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[]; meta: PaginationMeta }>>(
    `/api/admin/orders${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return { items: data.data.items.map(parseOrder), meta: data.data.meta };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const fetchAdminOrderById = async (
  orderId: number,
): Promise<{ order: OrderDto; events: OrderEventDto[]; processing: OrderProcessingDto }> => {
  const { data } = await httpClient.get<
    ApiResponse<{
      order: OrderDto;
      events: OrderEventDto[];
      processing: OrderProcessingDto;
    }>
  >(`/api/admin/orders/${orderId}`);
  if (isApiOk(data)) {
    return {
      order: parseOrder(data.data.order),
      events: data.data.events.map(parseOrderEvent),
      processing: parseOrderProcessing(data.data.processing),
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger la commande';
  throw new Error(message);
};

export const updateAdminOrderStatus = async (
  orderId: number,
  status: OrderStatus,
): Promise<OrderDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/status`,
    { status },
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de mettre à jour le statut';
  throw new Error(message);
};

export const updateAdminOrderDelivery = async (
  orderId: number,
  payload: {
    status: string;
    carrier?: string | null;
    trackingNumber?: string | null;
    trackingUrl?: string | null;
    estimatedAt?: string | null;
  },
): Promise<OrderDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/delivery`,
    payload,
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message =
    data.status === 'error' ? data.message : 'Impossible de mettre à jour la livraison';
  throw new Error(message);
};

export const retryAdminOrderInvoice = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/retry-invoice`,
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de regénérer la facture';
  throw new Error(message);
};

export const resendAdminOrderEmail = async (
  orderId: number,
  scenario: 'order_created' | 'invoice_issued' | 'current_status',
): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/resend-email`,
    { scenario },
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de renvoyer l’email';
  throw new Error(message);
};
