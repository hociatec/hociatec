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

const nonEmpty = (value?: string | null) => {
  const trimmed = value?.trim();
  return trimmed ? trimmed : null;
};

export const getCatalogProductConfiguration = (product: CatalogProduct) => {
  const variantSummary = (product.variantAttributes ?? [])
    .map((attribute) => {
      const values = attribute.values
        .map((value) => nonEmpty(value))
        .filter((value): value is string => Boolean(value));

      if (values.length === 0) {
        return null;
      }

      return values.join(' / ');
    })
    .filter((value): value is string => Boolean(value))
    .join(' • ');

  if (variantSummary) {
    return variantSummary;
  }

  const dynamicValues = (product.attributes ?? [])
    .map((attribute) => nonEmpty(attribute.value))
    .filter((value): value is string => Boolean(value));

  if (dynamicValues.length > 0) {
    return Array.from(new Set(dynamicValues)).join(' • ');
  }

  return nonEmpty(product.brand);
};

export const getCatalogVariantSummaries = (product: CatalogProduct) => {
  const dynamicSummaries = (product.variantAttributes ?? [])
    .map((attribute) => {
      const values = attribute.values
        .map((value) => nonEmpty(value))
        .filter((value): value is string => Boolean(value));

      if (values.length === 0) {
        return null;
      }

      return {
        code: attribute.code,
        label: attribute.label,
        values,
      };
    })
    .filter(
      (
        attribute,
      ): attribute is {
        code: string;
        label: string;
        values: string[];
      } => Boolean(attribute),
    );

  if (dynamicSummaries.length > 0) {
    return dynamicSummaries;
  }

  return [];
};
