import type { CatalogProduct } from '../api';
import { formatFrenchDate, formatEuroCents, formatSchemaPriceCents } from '@/shared/lib/formatters';
import { SITE_URL } from '@/shared/config/seoConfig';

export const formatProductPrice = formatEuroCents;
export const formatProductDate = formatFrenchDate;
export const resolveDisplayPriceCents = (
  product: Pick<
    CatalogProduct,
    'effectivePriceCents' | 'priceCents' | 'variantsCount' | 'minVariantEffectivePriceCents' | 'minVariantPriceCents'
  >,
) => {
  if ((product.variantsCount ?? 1) > 1) {
    return product.minVariantEffectivePriceCents ??
      product.minVariantPriceCents ??
      product.effectivePriceCents ??
      product.priceCents;
  }

  return product.effectivePriceCents ?? product.priceCents;
};

export const buildVariantGroupKey = (product: CatalogProduct) =>
  product.variantGroup?.trim() ||
  product.name
    .replace(/\s*\([^)]*\)\s*$/u, '')
    .replace(/\s*\([^)]*\)\s*$/u, '')
    .trim() ||
  product.sku;

export const buildProductSlides = (product: CatalogProduct | null, displayName?: string | null) => {
  if (!product) return [];
  if (product.gallery.length > 0) return product.gallery;
  return product.imageUrl
    ? [
        {
          position: 0,
          url: product.imageUrl,
          alt: product.imageAlt ?? displayName ?? product.name,
          isPrimary: true,
        },
      ]
    : [];
};

export const buildProductVariantOptions = (variants: CatalogProduct[]) =>
  [...variants]
    .sort(
      (left, right) =>
        (left.variantPosition ?? Number.MAX_SAFE_INTEGER) -
          (right.variantPosition ?? Number.MAX_SAFE_INTEGER) || left.id - right.id,
    )
    .map((variant) => {
      const storage = variant.storageCapacity?.trim() || null;
      const color = variant.color?.trim() || null;
      return {
        id: variant.id,
        slug: variant.slug,
        title: color ?? storage ?? variant.name,
        subtitle: storage ? `Stockage : ${storage}` : 'Version disponible',
        storage,
        color,
        priceLabel: `${formatProductPrice(resolveDisplayPriceCents(variant))}${variant.priceUnitLabel ? ` ${variant.priceUnitLabel}` : ''}`,
        isAvailable: variant.stock > 0,
        position: variant.variantPosition ?? Number.MAX_SAFE_INTEGER,
      };
    });

export const groupProductVariants = <T extends { id: number; storage: string | null; position: number }>(
  variants: T[],
) => {
  const groups = new Map<string, T[]>();
  variants.forEach((variant) =>
    groups.set(variant.storage ?? 'Autres versions', [
      ...(groups.get(variant.storage ?? 'Autres versions') ?? []),
      variant,
    ]),
  );
  return Array.from(groups.entries()).map(([storage, items]) => ({
    storage,
    items: items.sort((left, right) => left.position - right.position || left.id - right.id),
  }));
};

const toAbsoluteUrl = (url: string | null | undefined) => {
  if (!url) return undefined;

  try {
    return new URL(url, SITE_URL).toString();
  } catch {
    return undefined;
  }
};

export const buildProductStructuredData = (
  product: CatalogProduct,
  productDisplayName: string,
  canonicalUrl: string,
) => {
  const productUrl = toAbsoluteUrl(canonicalUrl) ?? canonicalUrl;
  const imageUrl = toAbsoluteUrl(product.imageUrl);
  const priceCents = resolveDisplayPriceCents(product);
  const schemas: Record<string, unknown>[] = [
    {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: productDisplayName,
      description:
        product.shortDescription ?? 'Une solution personnalisée pour vos besoins numériques.',
      sku: product.sku,
      brand: product.brand ? { '@type': 'Brand', name: product.brand } : undefined,
      category: product.category.name,
      image: imageUrl ? [imageUrl] : undefined,
      url: productUrl,
      offers: {
        '@type': 'Offer',
        url: productUrl,
        priceCurrency: 'EUR',
        price: formatSchemaPriceCents(priceCents),
        availability:
          product.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        itemCondition: 'https://schema.org/NewCondition',
      },
      aggregateRating:
        product.reviews && product.reviews.count > 0
          ? {
              '@type': 'AggregateRating',
              ratingValue: product.reviews.average,
              reviewCount: product.reviews.count,
            }
          : undefined,
    },
    {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        {
          '@type': 'ListItem',
          position: 1,
          name: 'Accueil',
          item: SITE_URL,
        },
        {
          '@type': 'ListItem',
          position: 2,
          name: product.category.name,
          item: `${SITE_URL}/catalogue/${product.category.slug}`,
        },
        {
          '@type': 'ListItem',
          position: 3,
          name: productDisplayName,
          item: productUrl,
        },
      ],
    },
  ];

  return schemas;
};
