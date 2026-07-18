import axios from 'axios';

import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { OrderDto } from '@/features/orders/api';

export interface SupportRequestDto {
  id: number;
  status: string;
  statusLabel: string;
  reason: string;
  subject: string;
  message?: string | null;
  internalNotes?: string | null;
  customer: { id: number; name: string; email: string };
  order?: { id: number; number: string } | null;
  createdAt: string;
  updatedAt: string;
  resolvedAt?: string | null;
}

export interface FulfillmentOrderDto {
  id: number;
  number: string;
  status: string;
  statusLabel: string;
  customer: { id: number; name: string; email: string };
  totalPriceCents: number;
  shipping: { name?: string | null; address?: string | null; postalCode?: string | null; city?: string | null };
  delivery: { status: string; statusLabel: string; carrier?: string | null; trackingNumber?: string | null; trackingUrl?: string | null };
  items: Array<{ name: string; sku: string; quantity: number }>;
  createdAt: string;
}

export interface RefundRequestDto {
  id: number;
  order: { id: number; number: string };
  paymentId?: number | null;
  amountCents: number;
  currencyCode: string;
  status: string;
  reason?: string | null;
  internalNotes?: string | null;
  stripeRefundId?: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface StockMovementDto {
  id: number;
  product: { id: number; name: string; sku: string };
  delta: number;
  stockBefore: number;
  stockAfter: number;
  reason: string;
  note?: string | null;
  actor?: string | null;
  createdAt: string;
}

export interface EmailLogDto {
  type: string;
  scenario: string;
  scenarioLabel?: string;
  status: string;
  statusLabel?: string;
  recipient?: string | null;
  subject?: string | null;
  related?: { type: string; id: number; label: string };
  createdAt: string;
}

export interface OperationsOverviewDto {
  support: { openCount: number; items: SupportRequestDto[] };
  refunds: { pendingCount: number; items: RefundRequestDto[] };
  stock: {
    lowStockThreshold?: number;
    lowStockCount: number;
    lowStockItems?: Array<{ id: number; name: string; sku: string; stock: number; lowStockThreshold?: number; category: string }>;
    movements: StockMovementDto[];
  };
  emails: { items: EmailLogDto[] };
  actions: Array<{ label: string; href: string }>;
}

const unwrap = <T>(data: ApiResponse<T>, fallback: string): T => {
  if (isApiOk(data)) return data.data as T;
  throw new Error(data.status === 'error' ? data.message : fallback);
};

const rethrowApiError = (error: unknown, fallback: string): never => {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiResponse<unknown> | undefined;
    if (data?.status === 'error' && data.message) {
      throw new Error(data.message);
    }
  }

  throw new Error(error instanceof Error ? error.message : fallback);
};

export const fetchOperationsOverview = async (): Promise<OperationsOverviewDto> => {
  const { data } = await httpClient.get<ApiResponse<OperationsOverviewDto>>('/api/admin/operations/overview');
  return unwrap(data, 'Impossible de charger l’exploitation admin');
};

export const fetchSupportRequests = async (): Promise<SupportRequestDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: SupportRequestDto[] }>>('/api/admin/operations/support-requests');
  return unwrap(data, 'Impossible de charger les demandes SAV').items ?? [];
};

export const createSupportRequest = async (payload: {
  customerId: number;
  orderId?: number | null;
  subject: string;
  reason: string;
  message?: string;
  internalNotes?: string;
}): Promise<SupportRequestDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: SupportRequestDto }>>('/api/admin/operations/support-requests', payload);
  return unwrap(data, 'Impossible de créer la demande SAV').item;
};

export const updateSupportRequest = async (id: number, payload: Partial<Pick<SupportRequestDto, 'status' | 'subject' | 'internalNotes'>>): Promise<SupportRequestDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ item: SupportRequestDto }>>(`/api/admin/operations/support-requests/${id}`, payload);
  return unwrap(data, 'Impossible de mettre à jour la demande SAV').item;
};

export const replySupportRequest = async (id: number, payload: { subject: string; message: string; status?: string }): Promise<SupportRequestDto> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ sent: boolean; item: SupportRequestDto }>>(`/api/admin/operations/support-requests/${id}/reply`, payload);
    return unwrap(data, 'Impossible d’envoyer la réponse SAV').item;
  } catch (error) {
    return rethrowApiError(error, 'Impossible d’envoyer la réponse SAV');
  }
};

export const fetchRefunds = async (): Promise<RefundRequestDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: RefundRequestDto[] }>>('/api/admin/operations/refunds');
  return unwrap(data, 'Impossible de charger les remboursements').items ?? [];
};

export const createRefund = async (payload: {
  orderId: number;
  amountCents: number;
  paymentId?: number | null;
  reason?: string;
  internalNotes?: string;
}): Promise<RefundRequestDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: RefundRequestDto }>>('/api/admin/operations/refunds', payload);
  return unwrap(data, 'Impossible de créer le remboursement').item;
};

export const updateRefund = async (id: number, payload: Partial<Pick<RefundRequestDto, 'status' | 'stripeRefundId' | 'internalNotes'>>): Promise<RefundRequestDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ item: RefundRequestDto }>>(`/api/admin/operations/refunds/${id}`, payload);
  return unwrap(data, 'Impossible de mettre à jour le remboursement').item;
};

export const processStripeRefund = async (id: number, payload: { confirmation: string; paymentIntentId?: string }): Promise<RefundRequestDto> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ item: RefundRequestDto; stripeRefund: Record<string, unknown> }>>(`/api/admin/operations/refunds/${id}/process-stripe`, payload);
    return unwrap(data, 'Impossible de déclencher le remboursement Stripe').item;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de déclencher le remboursement Stripe');
  }
};

export const fetchStockMovements = async (): Promise<StockMovementDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: StockMovementDto[] }>>('/api/admin/operations/stock-movements');
  return unwrap(data, 'Impossible de charger les mouvements de stock').items ?? [];
};

export const createStockMovement = async (payload: {
  productId: number;
  delta: number;
  reason: string;
  note?: string;
}): Promise<StockMovementDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: StockMovementDto }>>('/api/admin/operations/stock-movements', payload);
  return unwrap(data, 'Impossible de créer le mouvement de stock').item;
};

export const updateLowStockThreshold = async (productId: number, threshold: number): Promise<void> => {
  try {
    const { data } = await httpClient.patch<ApiResponse<{ product: unknown }>>(`/api/admin/operations/products/${productId}/low-stock-threshold`, { threshold });
    unwrap(data, 'Impossible de modifier le seuil de stock');
  } catch (error) {
    rethrowApiError(error, 'Impossible de modifier le seuil de stock');
  }
};

export const fetchFulfillmentOrders = async (): Promise<FulfillmentOrderDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: FulfillmentOrderDto[] }>>('/api/admin/operations/fulfillment/orders');
  return unwrap(data, 'Impossible de charger les commandes à préparer').items ?? [];
};

export const shipFulfillmentOrder = async (id: number, payload: { carrier?: string; trackingNumber?: string; trackingUrl?: string }): Promise<FulfillmentOrderDto> => {
  try {
    const { data } = await httpClient.patch<ApiResponse<{ order: FulfillmentOrderDto }>>(`/api/admin/operations/fulfillment/orders/${id}/ship`, payload);
    return unwrap(data, 'Impossible de marquer la commande expédiée').order;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de marquer la commande expédiée');
  }
};

export const fetchEmailLogs = async (): Promise<EmailLogDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: EmailLogDto[] }>>('/api/admin/operations/email-logs');
  return unwrap(data, 'Impossible de charger les emails').items ?? [];
};

export const bulkUpdateOrderStatus = async (orderIds: number[], status: string): Promise<number> => {
  const { data } = await httpClient.post<ApiResponse<{ updated: number }>>('/api/admin/operations/orders/bulk-status', { orderIds, status });
  return unwrap(data, 'Impossible de modifier les commandes').updated;
};

export const convertQuoteToOrder = async (quoteReference: string): Promise<OrderDto> => {
  try {
    const reference = encodeURIComponent(quoteReference.trim());
    const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(`/api/admin/operations/quotes/${reference}/convert-to-order`);
    return unwrap(data, 'Impossible de convertir le devis').order;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de convertir le devis');
  }
};
