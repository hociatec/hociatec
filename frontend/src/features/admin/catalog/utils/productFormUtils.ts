export const slugify = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

export const extractNumericValue = (value: string | null | undefined) => {
  if (!value) {
    return '';
  }

  const match = value.match(/\d+/);

  return match ? match[0] : '';
};

export const buildVariantIdentityKey = (color: string | null | undefined, storageCapacity: string | null | undefined) =>
  `${(color ?? '').trim().toLowerCase()}|${(storageCapacity ?? '').trim().toLowerCase()}`;

export const formatVariantConflictLabel = (color: string | null | undefined, storageCapacity: string | null | undefined) => {
  const parts = [color?.trim(), storageCapacity?.trim()].filter((value): value is string => Boolean(value));

  return parts.length > 0 ? parts.join(' / ') : 'cette variante';
};

export const formatVariantDetails = (product: { color?: string | null; storageCapacity?: string | null }) => {
  const details = [product.color, product.storageCapacity].filter(
    (value): value is string => Boolean(value && value.trim() !== ''),
  );

  return details.length > 0 ? details.join(' • ') : 'Aucune précision';
};

export const parseProductPrice = (value: string) => {
  const parsed = Number(value.replace(',', '.'));
  return Number.isNaN(parsed) ? -1 : parsed;
};
