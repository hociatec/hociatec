import type { CatalogProduct } from '../api';

const buildGroupKey = (product: CatalogProduct) =>
  product.variantGroup?.trim() ||
  product.name.replace(/\s*\([^)]*\)\s*$/u, '').replace(/\s*\([^)]*\)\s*$/u, '').trim() ||
  product.sku;

const compareVariantLead = (left: CatalogProduct, right: CatalogProduct) => {
  const leftPosition = left.variantPosition ?? Number.MAX_SAFE_INTEGER;
  const rightPosition = right.variantPosition ?? Number.MAX_SAFE_INTEGER;

  if (leftPosition !== rightPosition) {
    return leftPosition - rightPosition;
  }

  return left.id - right.id;
};

const collectVariantValues = (
  products: CatalogProduct[],
  selector: (product: CatalogProduct) => string | null | undefined,
) =>
  Array.from(
    new Set(
      products
        .map((product) => selector(product)?.trim())
        .filter((value): value is string => Boolean(value)),
    ),
  ).sort((left, right) => left.localeCompare(right, 'fr'));

const computeTotalStock = (products: CatalogProduct[]) =>
  products.reduce((total, product) => total + product.stock, 0);

export const groupCatalogProducts = (products: CatalogProduct[]): CatalogProduct[] => {
  const groups = new Map<string, CatalogProduct[]>();

  products.forEach((product) => {
    const key = buildGroupKey(product);
    const items = groups.get(key) ?? [];
    items.push(product);
    groups.set(key, items);
  });

  return Array.from(groups.values()).map((items) => {
    const sorted = [...items].sort(compareVariantLead);
    const lead = sorted[0];

    return {
      ...lead,
      variantsCount: sorted.length,
      totalStock: computeTotalStock(sorted),
      variantColors: collectVariantValues(sorted, (product) => product.color),
      variantStorages: collectVariantValues(sorted, (product) => product.storageCapacity),
    };
  });
};

export const groupMatchesFilters = (
  products: CatalogProduct[],
  predicate: (product: CatalogProduct) => boolean,
) => {
  const groups = new Map<string, CatalogProduct[]>();

  products.forEach((product) => {
    const key = buildGroupKey(product);
    const items = groups.get(key) ?? [];
    items.push(product);
    groups.set(key, items);
  });

  return Array.from(groups.values())
    .filter((items) => items.some(predicate))
    .map((items) => {
      const sorted = [...items].sort(compareVariantLead);
      const lead = sorted[0];

      return {
        ...lead,
        variantsCount: sorted.length,
        totalStock: computeTotalStock(sorted),
        variantColors: collectVariantValues(sorted, (product) => product.color),
        variantStorages: collectVariantValues(sorted, (product) => product.storageCapacity),
      };
    });
};
