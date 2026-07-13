import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

export interface ProductReviewDto {
  id: number;
  score: number;
  status: string;
  comment?: string | null;
  createdAt: string;
  publishedAt?: string | null;
  orderItemId?: number;
  author?: {
    id: number;
    displayName: string;
  };
}

export interface OrderItemDto {
  orderItemId: number;
  productId: number | null;
  productName: string;
  productSku: string;
  quantity: number;
  unitPriceCents: number;
  linePriceCents: number;
  canReview: boolean;
  review?: ProductReviewDto | null;
}

export interface OrderDto {
  id: number;
  number: string;
  status: 'pending' | 'confirmed' | 'delivered' | string;
  statusLabel?: string;
  totalPriceCents: number;
  createdAt: string;
  pendingReviewsCount?: number;
  hasPendingReviews?: boolean;
  shipping: {
    name: string | null;
    address: string | null;
    postalCode: string | null;
    city: string | null;
  };
  items: OrderItemDto[];
}

export interface PendingReviewDto {
  orderId: number;
  orderNumber: string;
  orderCreatedAt: string;
  orderItemId: number;
  product: {
    id: number;
    name: string;
    sku: string;
  } | null;
}

export const checkoutOrder = async (addressId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ data: OrderDto } | OrderDto>>(
    '/api/orders/checkout',
    { addressId },
  );

  // Our ApiResponse structure always wraps with status: 'ok' | 'error'
  if (isApiOk(data)) {
    // created returns the entity directly as data, normalize it
    const payload = (data.data as unknown) as Record<string, unknown>;
    const order = (payload.order ?? payload) as OrderDto;
    return order;
  }

  const message = data.status === 'error' ? data.message : 'Echec de validation de la commande';
  throw new Error(message);
};

export const fetchMyOrders = async (): Promise<OrderDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[] }>>('/api/orders/me');
  if (isApiOk(data)) {
    return (data.data?.items ?? []) as OrderDto[];
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const fetchOrderById = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.get<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}`,
  );
  if (isApiOk(data)) {
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
  }
  const message = data.status === 'error' ? data.message : 'Commande introuvable';
  throw new Error(message);
};

export const cancelMyOrder = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/orders/${orderId}/cancel`,
  );
  if (isApiOk(data)) {
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
  }
  const message = data.status === 'error' ? data.message : 'Impossible d\'annuler la commande';
  throw new Error(message);
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
    return (data.data?.review as ProductReviewDto) ?? ({} as ProductReviewDto);
  }

  const message = data.status === 'error' ? data.message : 'Impossible d\'enregistrer l\'avis';
  throw new Error(message);
};

export const fetchPendingReviews = async (): Promise<PendingReviewDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: PendingReviewDto[] }>>(
    '/api/orders/me/pending-reviews',
  );

  if (isApiOk(data)) {
    return (data.data?.items ?? []) as PendingReviewDto[];
  }

  const message = data.status === 'error' ? data.message : 'Impossible de charger les avis en attente';
  throw new Error(message);
};

// Admin API
export const fetchAdminOrders = async (
  status: 'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled' = 'all',
): Promise<OrderDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[] }>>(
    `/api/admin/orders${status && status !== 'all' ? `?status=${status}` : ''}`,
  );
  if (isApiOk(data)) {
    return (data.data?.items ?? []) as OrderDto[];
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const updateAdminOrderStatus = async (
  orderId: number,
  status: 'pending' | 'confirmed' | 'delivered' | 'cancelled',
): Promise<OrderDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/status`,
    { status },
  );
  if (isApiOk(data)) {
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de mettre a jour le statut';
  throw new Error(message);
};

export const formatOrderStatusFr = (status: string) => {
  switch (status) {
    case 'pending':
      return 'en attente';
    case 'confirmed':
      return 'confirmée';
    case 'delivered':
      return 'livrée';
    case 'cancelled':
      return 'annulée';
    default:
      return status;
  }
};
