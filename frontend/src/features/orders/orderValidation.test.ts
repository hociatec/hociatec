import { describe, expect, it } from 'vitest';

import { ApiContractError } from '@/shared/lib/contractValidation';
import { parseCheckoutRedirect, parseOrder } from './orderValidation';

const makeOrder = (overrides: Record<string, unknown> = {}) => ({
  id: 1,
  number: 'CMD-1',
  status: 'pending',
  allowedNextStatuses: ['confirmed'],
  allowedNextStatusDetails: [{ value: 'confirmed', label: 'Confirmée' }],
  totalPriceCents: 12990,
  createdAt: '2026-08-05T10:00:00Z',
  shipping: { name: null, address: null, postalCode: null, city: null },
  items: [
    {
      orderItemId: 5,
      productId: 1,
      productName: 'Produit',
      productSku: 'SKU-1',
      quantity: 1,
      unitPriceCents: 12990,
      linePriceCents: 12990,
      canReview: false,
    },
  ],
  ...overrides,
});

describe('parseOrder', () => {
  it('accepts a valid order payload', () => {
    expect(parseOrder(makeOrder())).toMatchObject({
      id: 1,
      number: 'CMD-1',
      status: 'pending',
    });
  });

  it('rejects invalid order statuses', () => {
    expect(() => parseOrder(makeOrder({ status: 'lost' }))).toThrow(ApiContractError);
  });
});

describe('parseCheckoutRedirect', () => {
  it('accepts trusted Stripe checkout redirect payloads', () => {
    expect(
      parseCheckoutRedirect({
        mode: 'redirect',
        checkoutUrl: 'https://checkout.stripe.com/c/pay/cs_test_123',
        checkoutSessionId: 'cs_test_123',
      }),
    ).toMatchObject({
      mode: 'redirect',
      checkoutSessionId: 'cs_test_123',
    });
  });

  it('rejects invalid checkout redirect payloads', () => {
    expect(() => parseCheckoutRedirect({ mode: 'inline', checkoutUrl: '/x' })).toThrow(ApiContractError);
  });

  it('rejects untrusted checkout redirect URLs', () => {
    expect(() => parseCheckoutRedirect({
      mode: 'redirect',
      checkoutUrl: 'https://example.com/pay',
      checkoutSessionId: 'cs_test_123',
    })).toThrow(ApiContractError);
  });
});
