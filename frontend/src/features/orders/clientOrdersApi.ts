import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import { downloadBlob } from './orderApiShared';
import type { OrderDto, PendingReviewDto, ProductReviewDto } from './orderTypes';

export interface CheckoutRedirectDto {
  mode: 'redirect';
  checkoutUrl: string;
  checkoutSessionId: string;
}

type CheckoutResponseDto = CheckoutRedirectDto | { order: OrderDto } | OrderDto;

export const checkoutOrder = async (addressId: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>(
    '/api/orders/checkout',
    { addressId },
  );

  if (isApiOk(data)) {
    const payload = data.data;
    if ('mode' in payload && payload.mode === 'redirect') {
      return payload;
    }

    return 'order' in payload ? payload.order : payload;
  }

  const message = data.status === 'error' ? data.message : 'Échec de validation de la commande';
  throw new Error(message);
};

export const checkoutExistingOrder = async (orderId: number, addressId?: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<CheckoutResponseDto>>(
    `/api/orders/${orderId}/checkout`,
    addressId ? { addressId } : {},
  );

  if (isApiOk(data)) {
    const payload = data.data;
    if ('mode' in payload && payload.mode === 'redirect') {
      return payload;
    }

    return 'order' in payload ? payload.order : payload;
  }

  const message = data.status === 'error' ? data.message : 'Impossible de lancer le règlement';
  throw new Error(message);
};

export const fetchCheckoutSessionStatus = async (
  stripeSessionId: string,
): Promise<{ status: string; checkoutSessionId: string; orderId?: number | null; order?: OrderDto | null }> => {
  const { data } = await httpClient.get<ApiResponse<{
    status: string;
    checkoutSessionId: string;
    orderId?: number | null;
    order?: OrderDto | null;
  }>>(`/api/orders/checkout/sessions/${encodeURIComponent(stripeSessionId)}`);
  if (isApiOk(data)) {
    return data.data as {
      status: string;
      checkoutSessionId: string;
      orderId?: number | null;
      order?: OrderDto | null;
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de vérifier le paiement';
  throw new Error(message);
};

export const fetchMyOrders = async (): Promise<OrderDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[] }>>('/api/orders/me');
  if (isApiOk(data)) {
    return data.data.items;
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const fetchOrderById = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.get<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}`,
  );
  if (isApiOk(data)) {
    return data.data.order;
  }
  const message = data.status === 'error' ? data.message : 'Commande introuvable';
  throw new Error(message);
};

export const cancelMyOrder = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}/cancel`,
  );
  if (isApiOk(data)) {
    return data.data.order;
  }
  const message = data.status === 'error' ? data.message : 'Impossible d\'annuler la commande';
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

  const message = data.status === 'error' ? data.message : 'Impossible d\'enregistrer l\'avis';
  throw new Error(message);
};

export const fetchPendingReviews = async (): Promise<PendingReviewDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: PendingReviewDto[] }>>(
    '/api/orders/me/pending-reviews',
  );

  if (isApiOk(data)) {
    return data.data.items;
  }

  const message = data.status === 'error' ? data.message : 'Impossible de charger les avis en attente';
  throw new Error(message);
};
