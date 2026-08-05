import type { OrderStatus } from '@/shared/contracts/statuses';

export interface ProductReviewDto {
  id: number;
  score: number;
  status: string;
  comment?: string | null | undefined;
  createdAt: string;
  publishedAt?: string | null | undefined;
  orderItemId?: number | undefined;
  author?: {
    id: number;
    displayName: string;
  } | undefined;
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
  statusLabel?: string | undefined;
  issuedAt?: string | null | undefined;
  billingName?: string | null | undefined;
  billingCompany?: string | null | undefined;
  billingCompanySiren?: string | null | undefined;
  billingCompanyVatNumber?: string | null | undefined;
  purchaseOrderNumber?: string | null | undefined;
  billingEmail?: string | null | undefined;
  billingAddress?: string | null | undefined;
  billingPostalCode?: string | null | undefined;
  billingCity?: string | null | undefined;
  currencyCode: string;
  electronicFormat: string;
}

export interface OrderDeliveryDto {
  status: string;
  statusLabel: string;
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

export interface AdminOrderMetadataDto {
  statuses: OrderStatusOptionDto[];
}

export interface OrderEventDto {
  id: number;
  type: string;
  message: string | null;
  createdAt: string;
  actor?: {
    id: number | null;
    name: string | null;
  } | undefined;
}

export interface OrderProcessingDto {
  invoicePdfGenerated: boolean;
  invoiceXmlGenerated: boolean;
  orderCreatedEmailSentAt?: string | null | undefined;
  invoiceEmailSentAt?: string | null | undefined;
  statusConfirmedEmailSentAt?: string | null | undefined;
  statusDeliveredEmailSentAt?: string | null | undefined;
  statusCancelledEmailSentAt?: string | null | undefined;
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
  shippingName?: string | null | undefined;
  shippingAddress?: string | null | undefined;
  shippingPostalCode?: string | null | undefined;
  shippingCity?: string | null | undefined;
  subtotalPriceCents?: number | undefined;
  discountAmountCents?: number | undefined;
  items?: Array<Record<string, unknown>> | undefined;
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
  status: OrderStatus;
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

export interface CheckoutRedirectDto {
  mode: 'redirect';
  checkoutUrl: string;
  checkoutSessionId: string;
}
