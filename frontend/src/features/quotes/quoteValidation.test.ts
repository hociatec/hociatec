import { describe, expect, it } from 'vitest';

import { ApiContractError } from '@/shared/lib/apiValidation';
import { parseQuote, parseQuoteService } from './quoteValidation';

const makeQuote = (overrides: Record<string, unknown> = {}) => ({
  id: 1,
  number: 'DEV-1',
  status: 'Brouillon',
  statusCode: 'draft',
  statusLabel: 'Brouillon',
  customer: {
    name: 'Client',
    email: 'client@example.test',
    company: null,
    address: null,
  },
  items: [
    {
      id: 10,
      type: 'custom',
      productId: null,
      serviceId: null,
      name: 'Diagnostic',
      description: null,
      unit: null,
      quantity: 1,
      unitPriceCents: 12000,
      vatRate: 20,
      discountCents: 0,
      lineTotals: { ht: 10000, vat: 2000, ttc: 12000 },
    },
  ],
  discountCents: 0,
  shippingCents: 0,
  conditions: null,
  validFrom: null,
  validUntil: null,
  totals: { ht: 10000, vat: 2000, ttc: 12000 },
  createdAt: '2026-08-05T10:00:00+00:00',
  updatedAt: '2026-08-05T10:00:00+00:00',
  sentAt: null,
  convertedOrder: null,
  ...overrides,
});

describe('parseQuote', () => {
  it('accepts valid quote payloads', () => {
    expect(parseQuote(makeQuote())).toMatchObject({
      id: 1,
      number: 'DEV-1',
      statusCode: 'draft',
    });
  });

  it('rejects unknown quote statuses', () => {
    expect(() => parseQuote(makeQuote({ statusCode: 'archived' }))).toThrow(ApiContractError);
  });
});

describe('parseQuoteService', () => {
  it('rejects unknown service duration units', () => {
    expect(() => parseQuoteService({
      id: 1,
      title: 'Intervention',
      description: null,
      unit: null,
      isFeaturedHome: false,
      durationValue: 2,
      durationUnit: 'week',
      durationLabel: '2 semaines',
      priceCents: 10000,
      vatRate: 20,
    })).toThrow(ApiContractError);
  });
});
