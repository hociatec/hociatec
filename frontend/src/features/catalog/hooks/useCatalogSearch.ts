import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import { searchPublicProducts } from '../api';
import {
  ALL_CATALOG_FILTER,
  CATALOG_PAGE_SIZE,
  emptyCatalogFacets,
  emptyCatalogMeta,
  normalizeCatalogFilter,
  normalizeCatalogSort,
  parseCatalogNumber,
} from '../lib/catalogSearch';
import { catalogQueryKeys } from '@/features/catalog/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

interface UseCatalogSearchOptions {
  category?: string;
  sellingType?: 'sale' | 'rental';
}

export const useCatalogSearch = ({
  category: fixedCategory,
  sellingType: fixedSellingType,
}: UseCatalogSearchOptions = {}) => {
  const [searchParams, setSearchParams] = useSearchParams();
  const query = searchParams.get('q') ?? '';
  const category = normalizeCatalogFilter(searchParams.get('category'));
  const sellingType = normalizeCatalogFilter(searchParams.get('sellingType'));
  const brand = normalizeCatalogFilter(searchParams.get('brand'));
  const storageCapacity = normalizeCatalogFilter(searchParams.get('storageCapacity'));
  const memoryRam = normalizeCatalogFilter(searchParams.get('memoryRam'));
  const color = normalizeCatalogFilter(searchParams.get('color'));
  const sort = normalizeCatalogSort(
    searchParams.get('sort'),
    query.trim() ? 'relevance' : 'release_year_desc',
  );
  const minPrice = parseCatalogNumber(searchParams.get('minPrice'));
  const maxPrice = parseCatalogNumber(searchParams.get('maxPrice'));
  const page = Math.max(1, parseCatalogNumber(searchParams.get('page')) ?? 1);
  const inStock = searchParams.get('inStock') === '1';
  const searchPayload = useMemo(
    () => omitUndefinedProperties({
      category: fixedCategory ?? (category !== ALL_CATALOG_FILTER ? category : undefined),
      q: query.trim() || undefined,
      sellingType:
        fixedSellingType ??
        (sellingType !== ALL_CATALOG_FILTER ? (sellingType as 'sale' | 'rental') : undefined),
      brand: brand !== ALL_CATALOG_FILTER ? brand : undefined,
      storageCapacity: storageCapacity !== ALL_CATALOG_FILTER ? storageCapacity : undefined,
      memoryRam: memoryRam !== ALL_CATALOG_FILTER ? memoryRam : undefined,
      color: color !== ALL_CATALOG_FILTER ? color : undefined,
      minPrice: minPrice ?? undefined,
      maxPrice: maxPrice ?? undefined,
      inStock,
      page,
      perPage: CATALOG_PAGE_SIZE,
      sort,
    }),
    [
      brand,
      category,
      color,
      fixedCategory,
      fixedSellingType,
      inStock,
      maxPrice,
      memoryRam,
      minPrice,
      page,
      query,
      sellingType,
      sort,
      storageCapacity,
    ],
  );
  const catalogQuery = useQuery<Awaited<ReturnType<typeof searchPublicProducts>>, Error>({
    queryKey: catalogQueryKeys.publicSearch(searchPayload),
    queryFn: ({ signal }) => searchPublicProducts({ ...searchPayload, signal }),
  });

  const updateParam = useCallback(
    (key: string, value: string | null) => {
      const next = new URLSearchParams(searchParams);
      if (value === null || value === '' || value === ALL_CATALOG_FILTER) next.delete(key);
      else next.set(key, value);
      if (key !== 'page') next.delete('page');
      setSearchParams(next, { replace: true });
    },
    [searchParams, setSearchParams],
  );

  const updatePriceRange = useCallback(
    (range: { min: number | null; max: number | null }) => {
      const next = new URLSearchParams(searchParams);
      if (range.min === null) next.delete('minPrice');
      else next.set('minPrice', String(range.min));
      if (range.max === null) next.delete('maxPrice');
      else next.set('maxPrice', String(range.max));
      next.delete('page');
      setSearchParams(next, { replace: true });
    },
    [searchParams, setSearchParams],
  );

  const resetFilters = useCallback(() => {
    const next = query.trim()
      ? new URLSearchParams({ q: query.trim(), sort: 'relevance' })
      : new URLSearchParams();
    setSearchParams(next, { replace: true });
  }, [query, setSearchParams]);

  return useMemo(
    () => ({
      products: catalogQuery.data?.items ?? [],
      meta: catalogQuery.data?.meta ?? emptyCatalogMeta,
      facets: catalogQuery.data?.facets ?? emptyCatalogFacets,
      loading: catalogQuery.isLoading,
      error: catalogQuery.error?.message ?? null,
      query,
      category,
      sellingType,
      brand,
      storageCapacity,
      memoryRam,
      color,
      sort,
      minPrice,
      maxPrice,
      inStock,
      updateParam,
      updatePriceRange,
      resetFilters,
    }),
    [
      brand,
      category,
      catalogQuery.data,
      catalogQuery.error,
      catalogQuery.isLoading,
      color,
      inStock,
      maxPrice,
      memoryRam,
      minPrice,
      query,
      resetFilters,
      sellingType,
      sort,
      storageCapacity,
      updateParam,
      updatePriceRange,
    ],
  );
};
