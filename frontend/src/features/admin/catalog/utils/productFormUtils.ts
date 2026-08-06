import { slugify as localSlugify } from '@/shared/lib/slugify';
import { parseNonNegativeDecimal } from '@/shared/lib/parsers';
import { normalizeSearchText } from '@/shared/lib/searchText';

export const slugify = (value: string) => localSlugify(value);

export const extractNumericValue = (value: string | null | undefined) => {
  if (!value) {
    return '';
  }

  const match = value.match(/\d+/);

  return match ? match[0] : '';
};

export const buildVariantIdentityKey = (
  color: string | null | undefined,
  storageCapacity: string | null | undefined,
) => `${normalizeTextValue(color)}|${normalizeTextValue(storageCapacity)}`;

export const normalizeTextValue = (value: string | null | undefined) =>
  normalizeSearchText(value).trim();

export const formatVariantConflictLabel = (
  color: string | null | undefined,
  storageCapacity: string | null | undefined,
) => {
  const parts = [color?.trim(), storageCapacity?.trim()].filter((value): value is string =>
    Boolean(value),
  );

  return parts.length > 0 ? parts.join(' / ') : 'cette variante';
};

export const formatVariantDetails = (product: {
  color?: string | null;
  storageCapacity?: string | null;
}) => {
  const details = [product.color, product.storageCapacity].filter((value): value is string =>
    Boolean(value && value.trim() !== ''),
  );

  return details.length > 0 ? details.join(' • ') : 'Aucune précision';
};

export const parseProductPrice = (value: string) => {
  return parseNonNegativeDecimal(value, Number.NaN);
};
