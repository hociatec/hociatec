import type { CatalogSearchFacets, CatalogSearchMeta, CatalogSort } from '../apiTypes';

import { clampAtLeast, clampWithin } from '@/shared/lib/number';

export const ALL_CATALOG_FILTER = 'all';
export const CATALOG_PAGE_SIZE = 10;

export const catalogSorts: CatalogSort[] = [
  'relevance',
  'price_asc',
  'price_desc',
  'release_year_desc',
  'release_year_asc',
  'name_desc',
  'stock_desc',
  'stock_asc',
  'created_desc',
];

export const normalizeCatalogSort = (value: string | null, fallback: CatalogSort): CatalogSort =>
  catalogSorts.includes(value as CatalogSort) ? (value as CatalogSort) : fallback;

export const normalizeCatalogFilter = (value: string | null): string =>
  value && value.trim() !== '' ? value : ALL_CATALOG_FILTER;

export const emptyCatalogFacets: CatalogSearchFacets = {
  brands: [],
  categories: [],
  storageCapacities: [],
  memoryRams: [],
  colors: [],
  price: { min: null, max: null },
};

export const emptyCatalogMeta: CatalogSearchMeta = {
  page: 1,
  perPage: CATALOG_PAGE_SIZE,
  total: 0,
  totalPages: 1,
};

export const getCatalogPageNumbers = (meta: CatalogSearchMeta) => {
  const start = clampAtLeast(meta.page - 2, 1);
  const end = clampWithin(meta.page + 2, 1, meta.totalPages);
  return Array.from({ length: end - start + 1 }, (_, index) => start + index);
};

export const formatCatalogResultsSummary = (total: number, query: string, noun: string) => {
  const suffix = total > 1 ? 's' : '';
  return query.trim()
    ? `${total} ${noun}${suffix} pour « ${query.trim()} »`
    : `${total} ${noun}${suffix} disponible${suffix}`;
};
