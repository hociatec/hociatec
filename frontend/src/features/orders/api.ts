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
  vatRateBps?: number;
  lineSubtotalCents?: number;
  lineVatCents?: number;
  linePriceCents: number;
  canReview: boolean;
  review?: ProductReviewDto | null;
}

export interface OrderInvoiceDto {
  number: string | null;
  status: string;
  statusLabel?: string;
  issuedAt?: string | null;
  billingName?: string | null;
  billingCompany?: string | null;
  billingCompanySiren?: string | null;
  billingCompanyVatNumber?: string | null;
  purchaseOrderNumber?: string | null;
  billingEmail?: string | null;
  billingAddress?: string | null;
  billingPostalCode?: string | null;
  billingCity?: string | null;
  currencyCode: string;
  electronicFormat: string;
}

export interface OrderDeliveryDto {
  status: string;
  statusLabel?: string;
  carrier?: string | null;
  trackingNumber?: string | null;
  trackingUrl?: string | null;
  estimatedAt?: string | null;
  shippedAt?: string | null;
  deliveredAt?: string | null;
}

export interface OrderEventDto {
  id: number;
  type: string;
  message: string | null;
  createdAt: string;
  actor?: {
    id: number | null;
    name: string | null;
  };
}

export interface OrderProcessingDto {
  invoicePdfGenerated: boolean;
  invoiceXmlGenerated: boolean;
  orderCreatedEmailSentAt?: string | null;
  invoiceEmailSentAt?: string | null;
  statusConfirmedEmailSentAt?: string | null;
  statusDeliveredEmailSentAt?: string | null;
  statusCancelledEmailSentAt?: string | null;
}

export interface AdminPaymentDto {
  id: number;
  status: string;
  statusLabel?: string;
  stripeSessionId: string;
  stripePaymentIntentId?: string | null;
  stripePaymentStatus?: string | null;
  stripePaymentStatusLabel?: string | null;
  failureCode?: string | null;
  failureMessage?: string | null;
  lastStripeEventType?: string | null;
  lastStripeEventLabel?: string | null;
  customerEmail: string;
  customerFullName?: string | null;
  totalPriceCents: number;
  currencyCode: string;
  orderId?: number | null;
  completedAt?: string | null;
  expiresAt?: string | null;
  createdAt: string;
}

export interface AdminPaymentDetailDto extends AdminPaymentDto {
  shippingName?: string | null;
  shippingAddress?: string | null;
  shippingPostalCode?: string | null;
  shippingCity?: string | null;
  subtotalPriceCents?: number;
  discountAmountCents?: number;
  items?: Array<Record<string, unknown>>;
}

export interface AdminPaymentLiveStripeDto {
  error?: string;
  checkoutSession?: {
    id?: string | null;
    status?: string | null;
    statusLabel?: string | null;
    paymentStatus?: string | null;
    paymentStatusLabel?: string | null;
    paymentIntent?: string | null;
    customerEmail?: string | null;
    expiresAt?: string | null;
  };
  paymentIntent?: {
    id?: string | null;
    status?: string | null;
    statusLabel?: string | null;
    amount?: number | null;
    currency?: string | null;
    error?: string;
    lastPaymentError?: {
      code?: string | null;
      declineCode?: string | null;
      message?: string | null;
      type?: string | null;
    };
  } | null;
}

export interface OrderDto {
  id: number;
  number: string;
  userId?: number;
  customerDisplayName?: string;
  status: 'pending' | 'confirmed' | 'delivered' | string;
  statusLabel?: string;
  subtotalPriceCents?: number;
  discountAmountCents?: number;
  totalPriceCents: number;
  appliedPromotion?: {
    name: string;
    slug: string | null;
  } | null;
  createdAt: string;
  pendingReviewsCount?: number;
  hasPendingReviews?: boolean;
  hasIssues?: boolean;
  issueReasons?: string[];
  payment?: AdminPaymentDto | null;
  shipping: {
    name: string | null;
    address: string | null;
    postalCode: string | null;
    city: string | null;
  };
  delivery?: OrderDeliveryDto | null;
  invoice?: OrderInvoiceDto | null;
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

const downloadBlob = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  window.URL.revokeObjectURL(url);
};

const normalizeFilenamePart = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');

export const buildOrderInvoiceFilename = (order: Pick<OrderDto, 'number' | 'createdAt' | 'shipping' | 'customerDisplayName'>) => {
  const date = new Date(order.createdAt);
  const datePart = Number.isNaN(date.getTime())
    ? 'date-inconnue'
    : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const clientName = normalizeFilenamePart(order.customerDisplayName || order.shipping?.name || 'client');
  const orderNumber = normalizeFilenamePart(order.number || 'commande');

  return `facture-${datePart}-${clientName}-${orderNumber}`;
};

export interface CheckoutRedirectDto {
  mode: 'redirect';
  checkoutUrl: string;
  checkoutSessionId: string;
}

export const checkoutOrder = async (addressId: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<Record<string, unknown>>>(
    '/api/orders/checkout',
    { addressId },
  );

  if (isApiOk(data)) {
    const payload = data.data as unknown as Record<string, unknown>;
    if (payload.mode === 'redirect') {
      return payload as unknown as CheckoutRedirectDto;
    }

    return (payload.order ?? payload) as OrderDto;
  }

  const message = data.status === 'error' ? data.message : 'Échec de validation de la commande';
  throw new Error(message);
};

export const checkoutExistingOrder = async (orderId: number, addressId?: number): Promise<OrderDto | CheckoutRedirectDto> => {
  const { data } = await httpClient.post<ApiResponse<Record<string, unknown>>>(
    `/api/orders/${orderId}/checkout`,
    addressId ? { addressId } : {},
  );

  if (isApiOk(data)) {
    const payload = data.data as unknown as Record<string, unknown>;
    if (payload.mode === 'redirect') {
      return payload as unknown as CheckoutRedirectDto;
    }

    return (payload.order ?? payload) as OrderDto;
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

export const fetchAdminOrders = async (
  status: 'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled' = 'all',
  health: 'all' | 'issues' = 'all',
): Promise<OrderDto[]> => {
  const query = new URLSearchParams();
  if (status && status !== 'all') {
    query.set('status', status);
  }
  if (health === 'issues') {
    query.set('health', health);
  }

  const { data } = await httpClient.get<ApiResponse<{ items: OrderDto[] }>>(
    `/api/admin/orders${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return (data.data?.items ?? []) as OrderDto[];
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les commandes';
  throw new Error(message);
};

export const fetchAdminOrderById = async (
  orderId: number,
): Promise<{ order: OrderDto; events: OrderEventDto[]; processing: OrderProcessingDto }> => {
  const { data } = await httpClient.get<ApiResponse<{
    order: OrderDto;
    events: OrderEventDto[];
    processing: OrderProcessingDto;
  }>>(`/api/admin/orders/${orderId}`);
  if (isApiOk(data)) {
    return {
      order: data.data?.order as OrderDto,
      events: (data.data?.events ?? []) as OrderEventDto[],
      processing: (data.data?.processing ?? {}) as OrderProcessingDto,
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger la commande';
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
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de mettre à jour la livraison';
  throw new Error(message);
};

export const retryAdminOrderInvoice = async (orderId: number): Promise<OrderDto> => {
  const { data } = await httpClient.post<ApiResponse<{ order: OrderDto }>>(
    `/api/admin/orders/${orderId}/retry-invoice`,
  );
  if (isApiOk(data)) {
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
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
    return (data.data?.order as OrderDto) ?? ({} as OrderDto);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de renvoyer l’email';
  throw new Error(message);
};

export const fetchAdminPayments = async (
  status: 'all' | 'open' | 'paid' | 'expired' | 'failed' = 'all',
  q = '',
): Promise<AdminPaymentDto[]> => {
  const query = new URLSearchParams();
  if (status && status !== 'all') {
    query.set('status', status);
  }
  if (q.trim() !== '') {
    query.set('q', q.trim());
  }

  const { data } = await httpClient.get<ApiResponse<{ items: AdminPaymentDto[] }>>(
    `/api/admin/payments${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return (data.data?.items ?? []) as AdminPaymentDto[];
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les paiements';
  throw new Error(message);
};

export const fetchAdminPaymentById = async (
  paymentId: number,
): Promise<{ payment: AdminPaymentDetailDto; liveStripe: AdminPaymentLiveStripeDto | null }> => {
  const { data } = await httpClient.get<ApiResponse<{
    payment: AdminPaymentDetailDto;
    liveStripe: AdminPaymentLiveStripeDto | null;
  }>>(`/api/admin/payments/${paymentId}`);
  if (isApiOk(data)) {
    return {
      payment: data.data?.payment as AdminPaymentDetailDto,
      liveStripe: (data.data?.liveStripe ?? null) as AdminPaymentLiveStripeDto | null,
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger le paiement';
  throw new Error(message);
};

export const formatOrderStatusFr = (status: string) => {
  switch (status) {
    case 'pending':
      return 'En attente';
    case 'confirmed':
      return 'Confirmée';
    case 'delivered':
      return 'Livrée';
    case 'cancelled':
      return 'Annulée';
    default:
      return status;
  }
};

export const formatInvoiceStatusFr = (status: string) => {
  switch (status) {
    case 'issued':
      return 'Émise';
    case 'cancelled':
      return 'Annulée';
    default:
      return status;
  }
};

export const formatPaymentStatusFr = (status: string) => {
  switch (status) {
    case 'open':
      return 'Ouvert';
    case 'paid':
      return 'Payé';
    case 'expired':
      return 'Expiré';
    case 'failed':
      return 'Échoué';
    default:
      return status;
  }
};

export const formatStripePaymentStatusFr = (status?: string | null) => {
  switch (status) {
    case 'paid':
      return 'Payé';
    case 'unpaid':
      return 'Non payé';
    case 'no_payment_required':
      return 'Aucun paiement requis';
    case 'requires_payment_method':
      return 'Moyen de paiement requis';
    case 'requires_confirmation':
      return 'Confirmation requise';
    case 'requires_action':
      return 'Action requise';
    case 'processing':
      return 'En cours de traitement';
    case 'succeeded':
      return 'Réussi';
    case 'canceled':
      return 'Annulé';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return status;
  }
};

export const formatStripeFailureCodeFr = (code?: string | null) => {
  switch (code) {
    case 'insufficient_funds':
      return 'Fonds insuffisants';
    case 'card_declined':
      return 'Carte refusée';
    case 'expired_card':
      return 'Carte expirée';
    case 'incorrect_cvc':
      return 'Code CVC incorrect';
    case 'incorrect_number':
      return 'Numéro de carte incorrect';
    case 'incorrect_zip':
      return 'Code postal incorrect';
    case 'invalid_cvc':
      return 'Code CVC invalide';
    case 'invalid_expiry_month':
      return 'Mois d’expiration invalide';
    case 'invalid_expiry_year':
      return 'Année d’expiration invalide';
    case 'lost_card':
      return 'Carte déclarée perdue';
    case 'stolen_card':
      return 'Carte déclarée volée';
    case 'processing_error':
      return 'Erreur de traitement bancaire';
    case 'authentication_required':
      return 'Authentification bancaire requise';
    case 'approve_with_id':
      return 'Paiement à faire approuver par la banque';
    case 'call_issuer':
      return 'Banque émettrice à contacter';
    case 'do_not_honor':
      return 'Paiement refusé par la banque';
    case 'generic_decline':
      return 'Refus bancaire générique';
    case 'pickup_card':
      return 'Carte à retenir';
    case 'reenter_transaction':
      return 'Transaction à ressaisir';
    case 'try_again_later':
      return 'Paiement à réessayer plus tard';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return code;
  }
};

export const formatStripeEventTypeFr = (eventType?: string | null) => {
  switch (eventType) {
    case 'checkout.session.completed':
      return 'Session de paiement finalisée';
    case 'checkout.session.async_payment_succeeded':
      return 'Paiement asynchrone confirmé';
    case 'checkout.session.async_payment_failed':
      return 'Paiement asynchrone échoué';
    case 'checkout.session.expired':
      return 'Session de paiement expirée';
    case 'payment_intent.payment_failed':
      return 'Paiement refusé';
    case undefined:
    case null:
    case '':
      return '-';
    default:
      return status;
  }
};
