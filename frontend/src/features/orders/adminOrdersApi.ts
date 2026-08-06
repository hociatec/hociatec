import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { OrderStatus, OrderStatusFilter } from '@/shared/contracts/statuses';
import type { AdminOrderMetadataDto, OrderDto, OrderEventDto, OrderProcessingDto } from './orderTypes';
import { parseOrder, parseOrderEvent, parseOrderProcessing } from './orderValidation';

export const fetchAdminOrderMetadata = async (): Promise<AdminOrderMetadataDto> => {
  const { data } = await httpClient.get<ApiResponse<AdminOrderMetadataDto>>('/api/admin/orders/metadata');
  if (isApiOk(data)) return data.data;
  throw new Error(extractApiErrorMessage(data, data.message));
};

export const fetchAdminOrders = async (
  status: OrderStatusFilter = 'all',
  health: 'all' | 'issues' = 'all',
  search = '',
  sort = 'newest',
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
  if (search.trim() !== '') {
    query.set('search', search.trim());
  }
  if (sort && sort !== 'newest') {
    query.set('sort', sort);
  }

  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[]; meta: PaginationMeta }>>(
    `/api/admin/orders${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return { items: data.data.items.map(parseOrder), meta: data.data.meta };
  }
  throw new Error(extractApiErrorMessage(data, 'Impossible de charger les commandes'));
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
  throw new Error(extractApiErrorMessage(data, 'Impossible de charger la commande'));
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
  throw new Error(extractApiErrorMessage(data, 'Impossible de mettre à jour le statut'));
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
  throw new Error(
    extractApiErrorMessage(data, 'Impossible de mettre à jour la livraison'),
  );
};

export const retryAdminOrderInvoice = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/retry-invoice`,
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  throw new Error(extractApiErrorMessage(data, 'Impossible de regénérer la facture'));
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
  throw new Error(extractApiErrorMessage(data, 'Impossible de renvoyer l’email'));
};
