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

export interface OrderStatusOptionDto {
  value: string;
  label: string;
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
  failureCodeLabel?: string | null;
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
  status: 'pending' | 'confirmed' | 'delivered' | 'cancelled';
  statusLabel?: string;
  allowedNextStatuses: OrderDto['status'][];
  allowedNextStatusDetails: OrderStatusOptionDto[];
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
