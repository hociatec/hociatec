import type { CatalogProduct } from '../api';

const buildGroupKey = (product: CatalogProduct) =>
  product.variantGroup?.trim() ||
  product.name
    .replace(/\s*\([^)]*\)\s*$/u, '')
    .replace(/\s*\([^)]*\)\s*$/u, '')
    .trim() ||
  product.sku;

const compareVariantLead = (left: CatalogProduct, right: CatalogProduct) => {
  const leftPosition = left.variantPosition ?? Number.MAX_SAFE_INTEGER;
  const rightPosition = right.variantPosition ?? Number.MAX_SAFE_INTEGER;

  if (leftPosition !== rightPosition) {
    return leftPosition - rightPosition;
  }

  return left.id - right.id;
};

const collectVariantAttributes = (products: CatalogProduct[]) => {
  const byCode = new Map<string, { code: string; label: string; values: string[] }>();

  products.forEach((product) => {
    (product.attributes ?? []).forEach((attribute) => {
      const code = attribute.code.trim();
      const label = attribute.label.trim();
      const value = attribute.value.trim();

      if (!code || !label || !value) {
        return;
      }

      const current = byCode.get(code) ?? { code, label, values: [] };
      if (!current.values.includes(value)) {
        current.values.push(value);
        current.values.sort((left, right) => left.localeCompare(right, 'fr'));
      }
      byCode.set(code, current);
    });
  });

  return Array.from(byCode.values()).sort((left, right) => left.label.localeCompare(right.label, 'fr'));
};

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
    if (!lead) throw new Error('Groupe de produits invalide.');

    return {
      ...lead,
      variantsCount: sorted.length,
      totalStock: computeTotalStock(sorted),
      variantAttributes: collectVariantAttributes(sorted),
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
        variantAttributes: collectVariantAttributes(sorted),
      };
    });
};
