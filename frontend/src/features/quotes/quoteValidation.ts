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
import { isContractValue, QUOTE_STATUSES } from '@/shared/contracts/statuses';
import type {
  AdminQuoteEmailDto,
  QuoteDto,
  QuoteItemDto,
  QuoteLineTotalsDto,
  QuoteServiceDto,
  QuoteStatus,
  QuoteToOrderDto,
} from './types/quoteTypes';

const QUOTE_ITEM_TYPES = new Set<QuoteItemDto['type']>(['service', 'product', 'custom']);
const SERVICE_DURATION_UNITS = new Set(['hour', 'day']);

const parseQuoteStatus = (value: unknown): QuoteStatus => {
  const status = requireString(value);
  if (!isContractValue(QUOTE_STATUSES, status)) {
    throw new ApiContractError('Réponse devis invalide.');
  }

  return status;
};

const parseLineTotals = (value: unknown): QuoteLineTotalsDto => {
  const totals = requireRecord(value);

  return {
    ht: requireNumber(totals.ht),
    vat: requireNumber(totals.vat),
    ttc: requireNumber(totals.ttc),
  };
};

const parseQuoteCustomer = (value: unknown): QuoteDto['customer'] => {
  const customer = requireRecord(value);

  return {
    name: optionalString(customer.name) ?? null,
    email: optionalString(customer.email) ?? null,
    company: optionalString(customer.company) ?? null,
    address: optionalString(customer.address) ?? null,
  };
};

const parseQuoteItem = (value: unknown): QuoteItemDto => {
  const item = requireRecord(value);
  const type = requireString(item.type);
  if (!QUOTE_ITEM_TYPES.has(type as QuoteItemDto['type'])) {
    throw new ApiContractError('Ligne de devis invalide.');
  }

  return {
    id: requireNumber(item.id),
    type: type as QuoteItemDto['type'],
    productId: optionalNumber(item.productId) ?? null,
    serviceId: optionalNumber(item.serviceId) ?? null,
    name: requireString(item.name),
    description: optionalString(item.description) ?? null,
    unit: optionalString(item.unit) ?? null,
    quantity: requireNumber(item.quantity),
    unitPriceCents: requireNumber(item.unitPriceCents),
    vatRate: requireNumber(item.vatRate),
    discountCents: requireNumber(item.discountCents),
    lineTotals: parseLineTotals(item.lineTotals),
  };
};

export const parseQuote = (value: unknown): QuoteDto => {
  const quote = requireRecord(value);

  return {
    id: requireNumber(quote.id),
    number: requireString(quote.number),
    status: requireString(quote.status),
    statusCode: parseQuoteStatus(quote.statusCode),
    statusLabel: requireString(quote.statusLabel),
    customer: parseQuoteCustomer(quote.customer),
    items: requireArray(quote.items).map(parseQuoteItem),
    discountCents: requireNumber(quote.discountCents),
    shippingCents: requireNumber(quote.shippingCents),
    conditions: optionalString(quote.conditions) ?? null,
    validFrom: optionalString(quote.validFrom) ?? null,
    validUntil: optionalString(quote.validUntil) ?? null,
    totals: parseLineTotals(quote.totals),
    createdAt: requireString(quote.createdAt),
    updatedAt: requireString(quote.updatedAt),
    sentAt: optionalString(quote.sentAt) ?? null,
    convertedOrder:
      quote.convertedOrder === null || quote.convertedOrder === undefined
        ? null
        : (() => {
            const order = requireRecord(quote.convertedOrder);
            return {
              id: requireNumber(order.id),
              number: requireString(order.number),
            };
          })(),
    emailNotificationSent: optionalBoolean(quote.emailNotificationSent) ?? undefined,
    emailNotificationError: optionalString(quote.emailNotificationError) ?? null,
  };
};

export const parseQuoteService = (value: unknown): QuoteServiceDto => {
  const service = requireRecord(value);
  const durationUnit = optionalString(service.durationUnit);
  if (durationUnit !== undefined && durationUnit !== null && !SERVICE_DURATION_UNITS.has(durationUnit)) {
    throw new ApiContractError('Service de devis invalide.');
  }

  return {
    id: requireNumber(service.id),
    title: requireString(service.title),
    description: optionalString(service.description) ?? null,
    unit: optionalString(service.unit) ?? null,
    isFeaturedHome: requireBoolean(service.isFeaturedHome),
    imageUrl: optionalString(service.imageUrl) ?? null,
    imageAlt: optionalString(service.imageAlt) ?? null,
    durationValue: optionalNumber(service.durationValue) ?? null,
    durationUnit: durationUnit === undefined ? null : durationUnit as QuoteServiceDto['durationUnit'],
    durationLabel: optionalString(service.durationLabel) ?? null,
    priceCents: requireNumber(service.priceCents),
    vatRate: requireNumber(service.vatRate),
  };
};

export const parseAdminQuoteEmail = (value: unknown): AdminQuoteEmailDto => {
  const email = requireRecord(value);

  return {
    sent: requireBoolean(email.sent),
    statusCode: email.statusCode === undefined ? undefined : parseQuoteStatus(email.statusCode),
    statusLabel: optionalString(email.statusLabel) ?? undefined,
    to: optionalString(email.to) ?? undefined,
    attachmentIncluded: optionalBoolean(email.attachmentIncluded) ?? undefined,
    transport: optionalString(email.transport) ?? undefined,
    message: optionalString(email.message) ?? undefined,
  };
};

export const parseQuoteToOrder = (value: unknown): QuoteToOrderDto => {
  const payload = requireRecord(value);
  const order = requireRecord(payload.order);

  return {
    order: {
      ...order,
      id: requireNumber(order.id),
      number: requireString(order.number),
    },
    emailNotificationSent: optionalBoolean(payload.emailNotificationSent) ?? undefined,
    emailNotificationError: optionalString(payload.emailNotificationError) ?? null,
  };
};
