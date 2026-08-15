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
      const normalizedAttributes = (variant.attributes ?? []).filter(
        (attribute) => attribute.value.trim() !== '',
      );
      const primaryAttribute = normalizedAttributes[0];
      const storage =
        normalizedAttributes.find((attribute) => attribute.code === 'storage')?.value.trim() ||
        variant.storageCapacity?.trim() ||
        null;
      const color =
        normalizedAttributes.find((attribute) => attribute.code === 'color')?.value.trim() ||
        variant.color?.trim() ||
        null;
      const title = primaryAttribute?.value.trim() || variant.name;
      const detailAttributes = normalizedAttributes
        .filter((attribute) => attribute.value.trim() !== title)
        .slice(0, 2)
        .map((attribute) => `${attribute.label} : ${attribute.value}`);
      const accessibilityParts = [
        title,
        ...detailAttributes,
        `Prix : ${formatProductPrice(resolveDisplayPriceCents(variant))}${variant.priceUnitLabel ? ` ${variant.priceUnitLabel}` : ''}`,
        variant.isPublished && variant.stock > 0 ? null : 'Indisponible',
      ].filter((value): value is string => Boolean(value));

      return {
        id: variant.id,
        slug: variant.slug,
        title,
        subtitle: detailAttributes.length > 0 ? detailAttributes.join(' • ') : 'Version disponible',
        groupLabel: primaryAttribute?.label ?? 'Variantes',
        groupValue: primaryAttribute?.value.trim() || null,
        storage,
        color,
        accessibilityLabel: accessibilityParts.join('. '),
        priceLabel: `${formatProductPrice(resolveDisplayPriceCents(variant))}${variant.priceUnitLabel ? ` ${variant.priceUnitLabel}` : ''}`,
        isAvailable: variant.stock > 0,
        position: variant.variantPosition ?? Number.MAX_SAFE_INTEGER,
      };
    });

export const groupProductVariants = <T extends { id: number; groupLabel?: string; groupValue?: string | null; position: number }>(
  variants: T[],
) => {
  const groups = new Map<string, T[]>();
  variants.forEach((variant) => {
    const key = variant.groupValue?.trim() || variant.groupLabel?.trim() || 'Autres versions';
    groups.set(key, [...(groups.get(key) ?? []), variant]);
  });
  return Array.from(groups.entries()).map(([label, items]) => ({
    label,
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
