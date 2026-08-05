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
import type { CheckoutRedirectDto } from './clientOrdersApi';
import type { OrderDto, OrderItemDto, OrderStatusOptionDto } from './orderTypes';

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
    delivery: order.delivery === undefined ? undefined : (order.delivery as OrderDto['delivery']),
    invoice: order.invoice === undefined ? undefined : (order.invoice as OrderDto['invoice']),
    items: requireArray(order.items).map(parseOrderItem),
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
