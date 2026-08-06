import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import {
  fetchPublicCategory,
  searchPublicProducts,
  type CatalogSearchFacets,
  type CatalogSearchMeta,
  type CatalogSort,
  type CategoryWithProducts,
} from '@/features/catalog/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { catalogQueryKeys } from '@/features/catalog/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

const emptyFacets: CatalogSearchFacets = {
  brands: [],
  categories: [],
  storageCapacities: [],
  memoryRams: [],
  colors: [],
  price: { min: null, max: null },
};
const initialMeta: CatalogSearchMeta = { page: 1, perPage: 10, total: 0, totalPages: 1 };

interface CategorySearchParams {
  slug?: string | undefined;
  search: string;
  brand: string;
  storageCapacity: string;
  memoryRam: string;
  color: string;
  minPrice: number | null;
  maxPrice: number | null;
  inStock: boolean;
  page: number;
  perPage: number;
  sort: CatalogSort;
}

export const useCategoryData = ({
  slug,
  search,
  brand,
  storageCapacity,
  memoryRam,
  color,
  minPrice,
  maxPrice,
  inStock,
  page,
  perPage,
  sort,
}: CategorySearchParams) => {
  const productsPayload = useMemo(
    () => omitUndefinedProperties({
      category: slug,
      q: search.trim() || undefined,
      brand: brand !== 'all' ? brand : undefined,
      storageCapacity: storageCapacity !== 'all' ? storageCapacity : undefined,
      memoryRam: memoryRam !== 'all' ? memoryRam : undefined,
      color: color !== 'all' ? color : undefined,
      minPrice: minPrice ?? undefined,
      maxPrice: maxPrice ?? undefined,
      ...(inStock ? { inStock } : {}),
      page,
      perPage,
      sort,
    }),
    [
      brand,
      color,
      inStock,
      maxPrice,
      memoryRam,
      minPrice,
      page,
      perPage,
      search,
      slug,
      sort,
      storageCapacity,
    ],
  );
  const categoryQuery = useQuery<CategoryWithProducts, Error>({
    queryKey: catalogQueryKeys.publicCategory(slug ?? null),
    queryFn: ({ signal }) => fetchPublicCategory(slug ?? '', { signal }),
    enabled: Boolean(slug),
  });
  const productsQuery = useQuery<Awaited<ReturnType<typeof searchPublicProducts>>, Error>({
    queryKey: catalogQueryKeys.publicCategoryProducts(productsPayload),
    queryFn: ({ signal }) => searchPublicProducts({ ...productsPayload, signal }),
    enabled: Boolean(slug),
  });
  const categoryError = categoryQuery.error
    ? getHttpErrorMessage(categoryQuery.error, "Cette catégorie n'est pas disponible pour le moment.")
    : null;
  const productsError = productsQuery.error
    ? getHttpErrorMessage(
        productsQuery.error,
        "Les produits de cette catégorie n'ont pas pu être chargés.",
      )
    : null;

  return {
    data: categoryQuery.data ?? null,
    products: productsQuery.data?.items ?? [],
    meta: productsQuery.data?.meta ?? initialMeta,
    facets: productsQuery.data?.facets ?? emptyFacets,
    loading: categoryQuery.isLoading || productsQuery.isLoading,
    error: categoryError ?? productsError,
    refresh: () =>
      Promise.all([categoryQuery.refetch(), productsQuery.refetch()]).then(() => undefined),
  };
};
