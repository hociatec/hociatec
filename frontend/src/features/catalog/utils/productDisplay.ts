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
  return stripTrailingVariantParts(product.modelName?.trim() || product.name);
};
