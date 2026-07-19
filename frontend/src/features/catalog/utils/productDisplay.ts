import type { CatalogProduct } from '../api';

const trailingParenthesesPattern = /\s*\([^)]*\)\s*$/u;

const stripTrailingVariantParts = (name: string) => {
  let normalized = name.trim();

  while (trailingParenthesesPattern.test(normalized)) {
    normalized = normalized.replace(trailingParenthesesPattern, '').trim();
  }

  return normalized;
};

export const getCatalogProductDisplayName = (product: CatalogProduct) => {
  const baseName = stripTrailingVariantParts(product.name);
  const attributes = [product.color, product.storageCapacity]
    .map((value) => value?.trim())
    .filter((value): value is string => Boolean(value));

  if (attributes.length === 0) {
    return baseName;
  }

  return `${baseName} ${attributes.map((value) => `(${value})`).join(' ')}`;
};
