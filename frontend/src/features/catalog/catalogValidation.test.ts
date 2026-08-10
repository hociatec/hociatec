import { describe, expect, it } from 'vitest';

import { ApiContractError } from '@/shared/lib/contractValidation';
import { parseCatalogProduct } from './catalogValidation';

const makeProduct = (overrides: Record<string, unknown> = {}) => ({
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
  ...overrides,
});

describe('parseCatalogProduct', () => {
  it('accepts a valid catalog product payload', () => {
    expect(parseCatalogProduct(makeProduct())).toMatchObject({
      id: 1,
      sku: 'SKU-1',
      sellingType: 'sale',
      priceCents: 12990,
    });
  });

  it('rejects invalid enum values from the API', () => {
    expect(() => parseCatalogProduct(makeProduct({ sellingType: 'lease' }))).toThrow(ApiContractError);
  });

  it('normalizes relative media urls returned by the API', () => {
    expect(parseCatalogProduct(makeProduct({
      imageUrl: '/uploads/products/produit.jpg',
      gallery: [{ position: 0, url: '/uploads/products/produit.jpg', alt: 'Produit', isPrimary: true }],
    }))).toMatchObject({
      imageUrl: expect.stringContaining('/uploads/products/produit.jpg'),
      gallery: [{ position: 0, url: expect.stringContaining('/uploads/products/produit.jpg'), alt: 'Produit', isPrimary: true }],
    });
  });
});
