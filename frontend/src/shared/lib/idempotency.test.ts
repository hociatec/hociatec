import { describe, expect, it } from 'vitest';

import { createBusinessIdempotencyKey } from './idempotency';

describe('business idempotency keys', () => {
  it('creates stable keys regardless of object key order', () => {
    expect(createBusinessIdempotencyKey('checkout.order', { addressId: 1, orderId: 2 })).toBe(
      createBusinessIdempotencyKey('checkout.order', { orderId: 2, addressId: 1 }),
    );
  });

  it('keeps different business operations separated', () => {
    expect(createBusinessIdempotencyKey('checkout.cart', { addressId: 1 })).not.toBe(
      createBusinessIdempotencyKey('quote.create', { addressId: 1 }),
    );
  });
});
