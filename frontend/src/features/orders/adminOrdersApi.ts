import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { OrderStatus, OrderStatusFilter } from '@/shared/contracts/statuses';
import type { AdminOrderMetadataDto, OrderDto, OrderEventDto, OrderProcessingDto } from './orderTypes';
import { parseOrder, parseOrderEvent, parseOrderProcessing } from './orderValidation';

export const fetchAdminOrderMetadata = async (): Promise<AdminOrderMetadataDto> => {
  const { data } = await httpClient.get<ApiResponse<AdminOrderMetadataDto>>('/api/admin/orders/metadata');
  return unwrapApiData(data, data.message ?? 'Réponse API invalide.');
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
    query.set('q', search.trim());
  }
  if (sort && sort !== 'newest') {
    query.set('sort', sort);
  }

  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[]; meta: PaginationMeta }>>(
    `/api/admin/orders${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  const payload = unwrapApiData(data, 'Impossible de charger les commandes');
  return { items: payload.items.map(parseOrder), meta: payload.meta };
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
  const payload = unwrapApiData(data, 'Impossible de charger la commande');
  return {
    order: parseOrder(payload.order),
    events: payload.events.map(parseOrderEvent),
    processing: parseOrderProcessing(payload.processing),
  };
};

export const updateAdminOrderStatus = async (
  orderId: number,
  status: OrderStatus,
): Promise<OrderDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/status`,
    { status },
  );
  const payload = unwrapApiData(data, 'Impossible de mettre à jour le statut');
  return parseOrder(payload.order);
};

export const updateAdminOrderDelivery = async (
  orderId: number,
  payloadData: {
    status: string;
    carrier?: string | null;
    trackingNumber?: string | null;
    trackingUrl?: string | null;
    estimatedAt?: string | null;
  },
): Promise<OrderDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/delivery`,
    payloadData,
  );
  const responsePayload = unwrapApiData(data, 'Impossible de mettre à jour la livraison');
  return parseOrder(responsePayload.order);
};

export const retryAdminOrderInvoice = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/retry-invoice`,
  );
  const payload = unwrapApiData(data, 'Impossible de regénérer la facture');
  return parseOrder(payload.order);
};

export const resendAdminOrderEmail = async (
  orderId: number,
  scenario: 'order_created' | 'invoice_issued' | 'current_status',
): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/resend-email`,
    { scenario },
  );
  const payload = unwrapApiData(data, 'Impossible de renvoyer l’email');
  return parseOrder(payload.order);
};
