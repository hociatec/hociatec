import { describe, expect, it } from 'vitest';

import type { CatalogProduct } from '../api';
import { groupCatalogProducts, groupMatchesFilters } from './groupProducts';

const makeProduct = (overrides: Partial<CatalogProduct>): CatalogProduct =>
  ({
    id: 1,
    name: 'Laptop Pro (Noir) (512 Go)',
    slug: 'laptop-pro-noir',
    sku: 'LP-1',
    shortDescription: null,
    description: '',
    priceCents: 129900,
    sellingType: 'sale',
    attributes: [],
    stock: 1,
    isPublished: true,
    isFeaturedHome: false,
    imageUrl: null,
    imageAlt: null,
    gallery: [],
    createdAt: '2026-01-01',
    updatedAt: '2026-01-01',
    category: { id: 1, name: 'Ordinateurs', slug: 'ordinateurs' },
    ...overrides,
  }) as CatalogProduct;

describe('groupCatalogProducts', () => {
  it('groups variants, keeps the lead variant and aggregates stock/attributes', () => {
    const grouped = groupCatalogProducts([
      makeProduct({
        id: 2,
        sku: 'LP-2',
        variantGroup: 'laptop-pro',
        variantPosition: 2,
        stock: 4,
        attributes: [{ code: 'color', label: 'Couleur', value: 'Bleu' }],
      }),
      makeProduct({
        id: 1,
        sku: 'LP-1',
        variantGroup: 'laptop-pro',
        variantPosition: 1,
        stock: 2,
        attributes: [{ code: 'color', label: 'Couleur', value: 'Noir' }],
      }),
    ]);

    expect(grouped).toHaveLength(1);
    expect(grouped[0]).toMatchObject({
      id: 1,
      variantsCount: 2,
      totalStock: 6,
      variantAttributes: [{ code: 'color', label: 'Couleur', values: ['Bleu', 'Noir'] }],
    });
  });
});

describe('groupMatchesFilters', () => {
  it('keeps a whole group when at least one variant matches the predicate', () => {
    const grouped = groupMatchesFilters(
      [
        makeProduct({
          id: 1,
          sku: 'LP-1',
          variantGroup: 'laptop-pro',
          attributes: [{ code: 'color', label: 'Couleur', value: 'Noir' }],
        }),
        makeProduct({
          id: 2,
          sku: 'LP-2',
          variantGroup: 'laptop-pro',
          attributes: [{ code: 'color', label: 'Couleur', value: 'Bleu' }],
        }),
      ],
      (product) => product.attributes?.some((attribute) => attribute.code === 'color' && attribute.value === 'Bleu') ?? false,
    );

    expect(grouped).toHaveLength(1);
    expect(grouped[0]?.variantsCount).toBe(2);
  });
});
