import { describe, expect, it } from 'vitest';

import { parseCart } from './cartValidation';

const product = {
  id: 1,
  name: 'Produit',
  slug: 'produit',
  sku: 'SKU-1',
  shortDescription: null,
  description: 'Description',
  priceCents: 12990,
  sellingType: 'sale',
  sellingTypeLabel: 'Achat',
  priceUnitLabel: null,
  stock: 4,
  isPublished: true,
  isFeaturedHome: false,
  imageUrl: null,
  imageAlt: null,
  gallery: [],
  createdAt: '2026-08-05T10:00:00Z',
  updatedAt: '2026-08-05T10:00:00Z',
  category: { id: 2, name: 'Catégorie', slug: 'categorie' },
};

describe('parseCart', () => {
  it('accepts a valid cart payload', () => {
    expect(
      parseCart({
        token: 'cart_token',
        items: [{ id: 10, product, quantity: 2, linePriceCents: 25980 }],
        totalQuantity: 2,
        subtotalPriceCents: 25980,
        discountAmountCents: 0,
        totalPriceCents: 25980,
        appliedPromotion: null,
        eligiblePromotions: [],
        appliedVoucher: null,
        voucherCodeStatus: 'none',
        updatedAt: '2026-08-05T10:00:00Z',
      }),
    ).toMatchObject({ token: 'cart_token', totalQuantity: 2 });
  });

  it('rejects invalid voucher status values', () => {
    expect(() =>
      parseCart({
        token: 'cart_token',
        items: [],
        totalQuantity: 0,
        subtotalPriceCents: 0,
        discountAmountCents: 0,
        totalPriceCents: 0,
        appliedPromotion: null,
        eligiblePromotions: [],
        appliedVoucher: null,
        voucherCodeStatus: 'unknown',
        updatedAt: '2026-08-05T10:00:00Z',
      }),
    ).toThrow('Réponse panier invalide.');
  });
});
