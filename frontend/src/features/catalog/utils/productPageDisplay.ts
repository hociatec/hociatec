import type { CatalogProduct } from '../api';
import { formatFrenchDate, formatEuroCents } from '@/shared/lib/formatters';

export const formatProductPrice = formatEuroCents;
export const formatProductDate = formatFrenchDate;

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
        priceLabel: `${formatProductPrice(variant.priceCents)}${variant.priceUnitLabel ? ` ${variant.priceUnitLabel}` : ''}`,
        stockLabel: variant.stock > 0 ? `${variant.stock} exemplaire${variant.stock > 1 ? 's' : ''} en stock` : 'Indisponible',
        isAvailable: variant.stock > 0,
      };
    });

export const groupProductVariants = <T extends { storage: string | null; title: string }>(
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
    items: items.sort((left, right) => left.title.localeCompare(right.title, 'fr')),
  }));
};
