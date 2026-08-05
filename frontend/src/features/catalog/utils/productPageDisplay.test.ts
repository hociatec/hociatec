import { describe, expect, it } from 'vitest';

import type { CatalogProduct } from '../api';
import { buildProductStructuredData } from './productPageDisplay';

const makeProduct = (overrides: Partial<CatalogProduct> = {}): CatalogProduct =>
  ({
    id: 1,
    name: 'Laptop Pro',
    slug: 'laptop-pro',
    sku: 'LP-1',
    shortDescription: 'Ordinateur portable professionnel.',
    description: '',
    priceCents: 129900,
    effectivePriceCents: 119900,
    sellingType: 'sale',
    sellingTypeLabel: 'Achat',
    priceUnitLabel: null,
    brand: 'Hociatec',
    stock: 2,
    isPublished: true,
    isFeaturedHome: false,
    imageUrl: '/uploads/laptop.webp',
    imageAlt: null,
    gallery: [],
    createdAt: '2026-01-01',
    updatedAt: '2026-01-01',
    category: { id: 1, name: 'Ordinateurs', slug: 'ordinateurs' },
    reviews: { count: 3, average: 4.5 },
    ...overrides,
  }) as CatalogProduct;

describe('buildProductStructuredData', () => {
  it('builds Product, Offer and BreadcrumbList schemas from visible product data', () => {
    const [productSchema, breadcrumbSchema] = buildProductStructuredData(
      makeProduct(),
      'Laptop Pro',
      'https://hociatec.fr/catalogue/produits/laptop-pro',
    );

    expect(productSchema).toMatchObject({
      '@type': 'Product',
      name: 'Laptop Pro',
      sku: 'LP-1',
      image: ['https://hociatec.fr/uploads/laptop.webp'],
      offers: {
        '@type': 'Offer',
        price: '1199.00',
        availability: 'https://schema.org/InStock',
      },
      aggregateRating: {
        ratingValue: 4.5,
        reviewCount: 3,
      },
    });
    expect(breadcrumbSchema).toMatchObject({
      '@type': 'BreadcrumbList',
      itemListElement: [
        { position: 1, name: 'Accueil' },
        { position: 2, name: 'Ordinateurs' },
        { position: 3, name: 'Laptop Pro' },
      ],
    });
  });

  it('marks unavailable products as out of stock', () => {
    const [productSchema] = buildProductStructuredData(
      makeProduct({ stock: 0 }),
      'Laptop Pro',
      'https://hociatec.fr/catalogue/produits/laptop-pro',
    );

    expect(productSchema.offers).toMatchObject({
      availability: 'https://schema.org/OutOfStock',
    });
  });
});
