import { describe, expect, it } from 'vitest';

import type { CatalogProduct } from '../api';
import { getCatalogProductDisplayName } from './productDisplay';

const makeProduct = (overrides: Partial<CatalogProduct>): CatalogProduct =>
  ({
    id: 1,
    name: 'iPhone 15 (Noir) (128 Go)',
    slug: 'iphone-15',
    sku: 'IPH-15',
    shortDescription: null,
    description: '',
    priceCents: 89900,
    sellingType: 'sale',
    stock: 3,
    isPublished: true,
    isFeaturedHome: false,
    imageUrl: null,
    imageAlt: null,
    gallery: [],
    createdAt: '2026-01-01',
    updatedAt: '2026-01-01',
    category: { id: 1, name: 'Smartphones', slug: 'smartphones' },
    ...overrides,
  }) as CatalogProduct;

describe('getCatalogProductDisplayName', () => {
  it('removes duplicated trailing variant fragments before appending current attributes', () => {
    expect(
      getCatalogProductDisplayName(
        makeProduct({
          color: 'Bleu',
          storageCapacity: '256 Go',
        }),
      ),
    ).toBe('iPhone 15 (Bleu) (256 Go)');
  });

  it('returns the normalized base name when no display attributes are available', () => {
    expect(
      getCatalogProductDisplayName(
        makeProduct({
          name: 'MacBook Air (Argent)',
          color: null,
          storageCapacity: null,
        }),
      ),
    ).toBe('MacBook Air');
  });
});
