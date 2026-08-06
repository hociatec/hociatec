import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import { downloadBlob } from './orderApiShared';
import type { CheckoutRedirectDto, OrderDto, PendingReviewDto, ProductReviewDto } from './orderTypes';
import { parseCheckoutRedirect, parseOrder, parsePendingReview } from './orderValidation';

type CheckoutResponseDto = CheckoutRedirectDto | { order: OrderDto } | OrderDto;

export const checkoutOrder = async (addressId: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>('/api/orders/checkout', {
    addressId,
  });

  if (isApiOk(data)) {
    const payload = data.data;
    if ('mode' in payload && payload.mode === 'redirect') {
      return parseCheckoutRedirect(payload);
    }

    return parseOrder('order' in payload ? payload.order : payload);
  }

  const message = data.status === 'error' ? data.message : 'Échec de validation de la commande';
  throw new Error(message);
};

export const checkoutExistingOrder = async (
  orderId: number,
  addressId?: number,
): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>(
    `/api/orders/${orderId}/checkout`,
    addressId ? { addressId } : {},
  );

  if (isApiOk(data)) {
    const payload = data.data;
    if ('mode' in payload && payload.mode === 'redirect') {
      return parseCheckoutRedirect(payload);
    }

    return parseOrder('order' in payload ? payload.order : payload);
  }

  const message = data.status === 'error' ? data.message : 'Impossible de lancer le règlement';
  throw new Error(message);
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
  if (isApiOk(data)) {
    return {
      ...data.data,
      order: data.data.order ? parseOrder(data.data.order) : null,
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de vérifier le paiement';
  throw new Error(message);
};

export const fetchMyOrders = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<OrderDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[]; meta: PaginationMeta }>>(
    '/api/orders/me',
    { params: { page, perPage } },
  );
  if (isApiOk(data)) {
    return { items: data.data.items.map(parseOrder), meta: data.data.meta };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const fetchOrderById = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.get<ApiResponse<{ order: OrderDto }>>(`/api/orders/${orderId}`);
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message = data.status === 'error' ? data.message : 'Commande introuvable';
  throw new Error(message);
};

export const cancelMyOrder = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}/cancel`,
  );
  if (isApiOk(data)) {
    return parseOrder(data.data.order);
  }
  const message = data.status === 'error' ? data.message : "Impossible d'annuler la commande";
  throw new Error(message);
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

  if (isApiOk(data)) {
    return data.data.review;
  }

  const message = data.status === 'error' ? data.message : "Impossible d'enregistrer l'avis";
  throw new Error(message);
};

export const fetchPendingReviews = async (): Promise<PendingReviewDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: PendingReviewDto[] }>>(
    '/api/orders/me/pending-reviews',
  );

  if (isApiOk(data)) {
    return data.data.items.map(parsePendingReview);
  }

  const message =
    data.status === 'error' ? data.message : 'Impossible de charger les avis en attente';
  throw new Error(message);
};
