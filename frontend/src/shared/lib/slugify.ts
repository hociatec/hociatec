import { normalizeSearchText } from '@/shared/lib/searchText';

export const slugify = (value: string) =>
  normalizeSearchText(value)
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
