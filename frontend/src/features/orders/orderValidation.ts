import {
  ApiContractError,
  requireArray,
  requireBoolean,
  requireNumber,
  requireRecord,
  requireString,
  optionalBoolean,
  optionalNumber,
  optionalString,
} from '@/shared/lib/apiValidation';
import type {
  AdminPaymentDetailDto,
  AdminPaymentDto,
  AdminPaymentLiveStripeDto,
  OrderDeliveryDto,
  OrderDto,
  OrderEventDto,
  OrderInvoiceDto,
  OrderItemDto,
  OrderProcessingDto,
  OrderStatusOptionDto,
  PendingReviewDto,
  ProductReviewDto,
  CheckoutRedirectDto,
} from './orderTypes';

const ORDER_STATUSES = new Set(['pending', 'confirmed', 'delivered', 'cancelled']);

const parseStatusOption = (value: unknown): OrderStatusOptionDto => {
  const item = requireRecord(value);
  return {
    value: requireString(item.value),
    label: requireString(item.label),
  };
};

const parseOrderItem = (value: unknown): OrderItemDto => {
  const item = requireRecord(value);
  return {
    ...item,
    orderItemId: requireNumber(item.orderItemId),
    productId: item.productId === null ? null : requireNumber(item.productId),
    productName: requireString(item.productName),
    productSku: requireString(item.productSku),
    quantity: requireNumber(item.quantity),
    unitPriceCents: requireNumber(item.unitPriceCents),
    vatRateBps: optionalNumber(item.vatRateBps) ?? undefined,
    lineSubtotalCents: optionalNumber(item.lineSubtotalCents) ?? undefined,
    lineVatCents: optionalNumber(item.lineVatCents) ?? undefined,
    linePriceCents: requireNumber(item.linePriceCents),
    canReview: requireBoolean(item.canReview),
  } as OrderItemDto;
};

const parseOrderDelivery = (value: unknown): OrderDeliveryDto | null | undefined => {
  if (value === undefined) return undefined;
  if (value === null) return null;
  const delivery = requireRecord(value);

  return {
    status: requireString(delivery.status),
    statusLabel: requireString(delivery.statusLabel),
    carrier: optionalString(delivery.carrier) ?? null,
    trackingNumber: optionalString(delivery.trackingNumber) ?? null,
    trackingUrl: optionalString(delivery.trackingUrl) ?? null,
    estimatedAt: optionalString(delivery.estimatedAt) ?? null,
    shippedAt: optionalString(delivery.shippedAt) ?? null,
    deliveredAt: optionalString(delivery.deliveredAt) ?? null,
  };
};

const parseOrderInvoice = (value: unknown): OrderInvoiceDto | null | undefined => {
  if (value === undefined) return undefined;
  if (value === null) return null;
  const invoice = requireRecord(value);

  return {
    number: optionalString(invoice.number) ?? null,
    status: requireString(invoice.status),
    statusLabel: optionalString(invoice.statusLabel) ?? undefined,
    issuedAt: optionalString(invoice.issuedAt) ?? null,
    billingName: optionalString(invoice.billingName) ?? null,
    billingCompany: optionalString(invoice.billingCompany) ?? null,
    billingCompanySiren: optionalString(invoice.billingCompanySiren) ?? null,
    billingCompanyVatNumber: optionalString(invoice.billingCompanyVatNumber) ?? null,
    purchaseOrderNumber: optionalString(invoice.purchaseOrderNumber) ?? null,
    billingEmail: optionalString(invoice.billingEmail) ?? null,
    billingAddress: optionalString(invoice.billingAddress) ?? null,
    billingPostalCode: optionalString(invoice.billingPostalCode) ?? null,
    billingCity: optionalString(invoice.billingCity) ?? null,
    currencyCode: requireString(invoice.currencyCode),
    electronicFormat: requireString(invoice.electronicFormat),
  };
};

const parseProductReview = (value: unknown): ProductReviewDto => {
  const review = requireRecord(value);
  const author =
    review.author === undefined
      ? undefined
      : (() => {
          const item = requireRecord(review.author);
          return {
            id: requireNumber(item.id),
            displayName: requireString(item.displayName),
          };
        })();

  return {
    id: requireNumber(review.id),
    score: requireNumber(review.score),
    status: requireString(review.status),
    comment: optionalString(review.comment) ?? undefined,
    createdAt: requireString(review.createdAt),
    publishedAt: optionalString(review.publishedAt) ?? undefined,
    orderItemId: optionalNumber(review.orderItemId) ?? undefined,
    author,
  };
};

export const parseOrder = (value: unknown): OrderDto => {
  const order = requireRecord(value);
  const status = requireString(order.status);
  if (!ORDER_STATUSES.has(status)) {
    throw new ApiContractError('Réponse commande invalide.');
  }

  const shipping = requireRecord(order.shipping);

  return {
    ...order,
    id: requireNumber(order.id),
    number: requireString(order.number),
    userId: optionalNumber(order.userId) ?? undefined,
    customerDisplayName: optionalString(order.customerDisplayName) ?? undefined,
    status: status as OrderDto['status'],
    statusLabel: optionalString(order.statusLabel) ?? undefined,
    allowedNextStatuses: requireArray(order.allowedNextStatuses).map((item) => {
      const nextStatus = requireString(item);
      if (!ORDER_STATUSES.has(nextStatus)) throw new ApiContractError('Réponse commande invalide.');
      return nextStatus as OrderDto['status'];
    }),
    allowedNextStatusDetails: requireArray(order.allowedNextStatusDetails).map(parseStatusOption),
    subtotalPriceCents: optionalNumber(order.subtotalPriceCents) ?? undefined,
    discountAmountCents: optionalNumber(order.discountAmountCents) ?? undefined,
    totalPriceCents: requireNumber(order.totalPriceCents),
    createdAt: requireString(order.createdAt),
    pendingReviewsCount: optionalNumber(order.pendingReviewsCount) ?? undefined,
    hasPendingReviews: optionalBoolean(order.hasPendingReviews) ?? undefined,
    hasIssues: optionalBoolean(order.hasIssues) ?? undefined,
    issueReasons: order.issueReasons === undefined ? undefined : requireArray(order.issueReasons).map((item) => requireString(item)),
    shipping: {
      name: optionalString(shipping.name) ?? null,
      address: optionalString(shipping.address) ?? null,
      postalCode: optionalString(shipping.postalCode) ?? null,
      city: optionalString(shipping.city) ?? null,
    },
    delivery: parseOrderDelivery(order.delivery),
    invoice: parseOrderInvoice(order.invoice),
    items: requireArray(order.items).map((item) => {
      const parsed = parseOrderItem(item);
      const raw = requireRecord(item);
      return {
        ...parsed,
        review:
          raw.review === undefined || raw.review === null ? raw.review as null | undefined : parseProductReview(raw.review),
      };
    }),
  } as OrderDto;
};

export const parseCheckoutRedirect = (value: unknown): CheckoutRedirectDto => {
  const redirect = requireRecord(value);
  if (redirect.mode !== 'redirect') throw new ApiContractError('Réponse paiement invalide.');

  return {
    mode: 'redirect',
    checkoutUrl: requireString(redirect.checkoutUrl),
    checkoutSessionId: requireString(redirect.checkoutSessionId),
  };
};

export const parseOrderEvent = (value: unknown): OrderEventDto => {
  const event = requireRecord(value);
  const actor =
    event.actor === undefined
      ? undefined
      : (() => {
          const item = requireRecord(event.actor);
          return {
            id: optionalNumber(item.id) ?? null,
            name: optionalString(item.name) ?? null,
          };
        })();

  return {
    id: requireNumber(event.id),
    type: requireString(event.type),
    message: optionalString(event.message) ?? null,
    createdAt: requireString(event.createdAt),
    actor,
  };
};

export const parseOrderProcessing = (value: unknown): OrderProcessingDto => {
  const processing = requireRecord(value);

  return {
    invoicePdfGenerated: requireBoolean(processing.invoicePdfGenerated),
    invoiceXmlGenerated: requireBoolean(processing.invoiceXmlGenerated),
    orderCreatedEmailSentAt: optionalString(processing.orderCreatedEmailSentAt) ?? undefined,
    invoiceEmailSentAt: optionalString(processing.invoiceEmailSentAt) ?? undefined,
    statusConfirmedEmailSentAt: optionalString(processing.statusConfirmedEmailSentAt) ?? undefined,
    statusDeliveredEmailSentAt: optionalString(processing.statusDeliveredEmailSentAt) ?? undefined,
    statusCancelledEmailSentAt: optionalString(processing.statusCancelledEmailSentAt) ?? undefined,
  };
};

export const parseAdminPayment = (value: unknown): AdminPaymentDto => {
  const payment = requireRecord(value);

  return {
    ...payment,
    id: requireNumber(payment.id),
    status: requireString(payment.status),
    statusLabel: optionalString(payment.statusLabel) ?? undefined,
    stripeSessionId: requireString(payment.stripeSessionId),
    stripePaymentIntentId: optionalString(payment.stripePaymentIntentId) ?? undefined,
    stripePaymentStatus: optionalString(payment.stripePaymentStatus) ?? undefined,
    stripePaymentStatusLabel: optionalString(payment.stripePaymentStatusLabel) ?? undefined,
    failureCode: optionalString(payment.failureCode) ?? undefined,
    failureCodeLabel: optionalString(payment.failureCodeLabel) ?? undefined,
    failureMessage: optionalString(payment.failureMessage) ?? undefined,
    lastStripeEventType: optionalString(payment.lastStripeEventType) ?? undefined,
    lastStripeEventLabel: optionalString(payment.lastStripeEventLabel) ?? undefined,
    customerEmail: requireString(payment.customerEmail),
    customerFullName: optionalString(payment.customerFullName) ?? undefined,
    totalPriceCents: requireNumber(payment.totalPriceCents),
    currencyCode: requireString(payment.currencyCode),
    orderId: optionalNumber(payment.orderId) ?? undefined,
    completedAt: optionalString(payment.completedAt) ?? undefined,
    expiresAt: optionalString(payment.expiresAt) ?? undefined,
    createdAt: requireString(payment.createdAt),
  } as AdminPaymentDto;
};

export const parseAdminPaymentDetail = (value: unknown): AdminPaymentDetailDto => {
  const payment = requireRecord(value);

  return {
    ...parseAdminPayment(payment),
    shippingName: optionalString(payment.shippingName) ?? undefined,
    shippingAddress: optionalString(payment.shippingAddress) ?? undefined,
    shippingPostalCode: optionalString(payment.shippingPostalCode) ?? undefined,
    shippingCity: optionalString(payment.shippingCity) ?? undefined,
    subtotalPriceCents: optionalNumber(payment.subtotalPriceCents) ?? undefined,
    discountAmountCents: optionalNumber(payment.discountAmountCents) ?? undefined,
    items:
      payment.items === undefined
        ? undefined
        : requireArray(payment.items).map((item) => requireRecord(item)),
  };
};

export const parseAdminPaymentLiveStripe = (value: unknown): AdminPaymentLiveStripeDto | null => {
  if (value === null || value === undefined) return null;
  return requireRecord(value) as AdminPaymentLiveStripeDto;
};

export const parsePendingReview = (value: unknown): PendingReviewDto => {
  const review = requireRecord(value);
  const product =
    review.product === null
      ? null
      : (() => {
          const item = requireRecord(review.product);
          return {
            id: requireNumber(item.id),
            name: requireString(item.name),
            sku: requireString(item.sku),
          };
        })();

  return {
    orderId: requireNumber(review.orderId),
    orderNumber: requireString(review.orderNumber),
    orderCreatedAt: requireString(review.orderCreatedAt),
    orderItemId: requireNumber(review.orderItemId),
    product,
  };
};
