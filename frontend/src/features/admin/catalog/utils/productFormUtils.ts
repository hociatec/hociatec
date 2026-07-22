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
