import { httpClient } from '@/shared/lib/httpClient';
import { idempotencyRequestConfig } from '@/shared/lib/idempotency';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import { downloadBlob } from './orderApiShared';
import type { CheckoutRedirectDto, OrderDto, PendingReviewDto, ProductReviewDto } from './orderTypes';
import { parseCheckoutRedirect, parseOrder, parsePendingReview } from './orderValidation';

type CheckoutResponseDto = CheckoutRedirectDto | { order: OrderDto } | OrderDto;

export const checkoutOrder = async (addressId: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>('/api/orders/checkout', {
    addressId,
  }, idempotencyRequestConfig('checkout.cart', { addressId }));
  const payload = unwrapApiData(data, 'Échec de validation de la commande');
  if ('mode' in payload && payload.mode === 'redirect') {
    return parseCheckoutRedirect(payload);
  }

  return parseOrder('order' in payload ? payload.order : payload);
};

export const checkoutExistingOrder = async (
  orderId: number,
  addressId?: number,
): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>(
    `/api/orders/${orderId}/checkout`,
    addressId ? { addressId } : {},
    idempotencyRequestConfig('checkout.order', { addressId: addressId ?? null, orderId }),
  );
  const payload = unwrapApiData(data, 'Impossible de lancer le règlement');
  if ('mode' in payload && payload.mode === 'redirect') {
    return parseCheckoutRedirect(payload);
  }
  return parseOrder('order' in payload ? payload.order : payload);
};

export const fetchCheckoutSessionStatus = async (
  stripeSessionId: string,
): Promise<{
  status: string;
  checkoutSessionId: string;
  orderId?: number | null;
  order?: OrderDto | null;
}> => {
  const { data } = await httpClient.get<
    ApiResponse<{
      status: string;
      checkoutSessionId: string;
      orderId?: number | null;
      order?: OrderDto | null;
    }>
  >(`/api/orders/checkout/sessions/${encodeURIComponent(stripeSessionId)}`);
  const payload = unwrapApiData(data, 'Impossible de vérifier le paiement');
  return {
    ...payload,
    order: payload.order ? parseOrder(payload.order) : null,
  };
};

export const fetchMyOrders = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<OrderDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[]; meta: PaginationMeta }>>(
    '/api/orders/me',
    { params: { page, perPage } },
  );
  const payload = unwrapApiData(data, 'Impossible de charger les commandes');
  return { items: payload.items.map(parseOrder), meta: payload.meta };
};

export const fetchOrderById = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.get<ApiResponse<{ order: OrderDto }>>(`/api/orders/${orderId}`);
  const payload = unwrapApiData(data, 'Commande introuvable');
  return parseOrder(payload.order);
};

export const cancelMyOrder = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}/cancel`,
    {},
    idempotencyRequestConfig('order.cancel', { orderId }),
  );
  const payload = unwrapApiData(data, "Impossible d'annuler la commande");
  return parseOrder(payload.order);
};

export const downloadOrderInvoicePdf = async (orderId: number, filenameBase: string) => {
  const response = await httpClient.get(`/api/orders/${orderId}/invoice/pdf`, {
    responseType: 'blob',
  });
  downloadBlob(response.data, `${filenameBase}.pdf`);
};

export const downloadOrderInvoiceXml = async (orderId: number, filenameBase: string) => {
  const response = await httpClient.get(`/api/orders/${orderId}/invoice/xml`, {
    responseType: 'blob',
  });
  downloadBlob(response.data, `${filenameBase}.xml`);
};

export const submitOrderItemReview = async (
  orderId: number,
  orderItemId: number,
  payload: { score: number; comment?: string },
): Promise<ProductReviewDto> => {
  const { data } = await httpClient.post<ApiResponse<{ review: ProductReviewDto }>>(
    `/api/orders/${orderId}/items/${orderItemId}/review`,
    payload,
  );

  return unwrapApiData(data, "Impossible d'enregistrer l'avis").review;
};

export const fetchPendingReviews = async (): Promise<PendingReviewDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: PendingReviewDto[] }>>(
    '/api/orders/me/pending-reviews',
  );

  return unwrapApiData(data, 'Impossible de charger les avis en attente').items.map(parsePendingReview);
};
