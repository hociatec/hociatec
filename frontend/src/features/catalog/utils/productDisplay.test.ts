import { describe, expect, it } from 'vitest';

import type { CatalogProduct } from '../api';
import { getCatalogProductConfiguration, getCatalogProductDisplayName } from './productDisplay';

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
  it('returns only the product model name for display', () => {
    expect(
      getCatalogProductDisplayName(
        makeProduct({
          color: 'Bleu',
          storageCapacity: '256 Go',
        }),
      ),
    ).toBe('iPhone 15');
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

describe('getCatalogProductConfiguration', () => {
  it('prefers grouped variant summaries so every available storage stays visible', () => {
    expect(
      getCatalogProductConfiguration(
        makeProduct({
          attributes: [
            { code: 'color', label: 'Couleur', value: 'Noir' },
            { code: 'storage', label: 'Stockage', value: '128 Go' },
          ],
          variantAttributes: [
            { code: 'color', label: 'Couleur', values: ['Noir', 'Bleu'] },
            { code: 'storage', label: 'Stockage', values: ['128 Go', '256 Go'] },
            { code: 'ram', label: 'RAM', values: ['8 Go'] },
          ],
        }),
      ),
    ).toBe('Noir / Bleu • 128 Go / 256 Go • 8 Go');
  });

  it('falls back to concrete product attributes when no grouped variant summary exists', () => {
    expect(
      getCatalogProductConfiguration(
        makeProduct({
          attributes: [
            { code: 'color', label: 'Couleur', value: 'Noir' },
            { code: 'storage', label: 'Stockage', value: '256 Go' },
          ],
        }),
      ),
    ).toBe('Noir • 256 Go');
  });
});
