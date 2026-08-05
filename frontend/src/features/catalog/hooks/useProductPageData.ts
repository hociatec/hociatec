import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchPublicProduct, fetchPublicProducts, type CatalogProduct, type CatalogSort } from '../api';
import { buildVariantGroupKey } from '../utils/productPageDisplay';
import { catalogQueryKeys } from '@/shared/lib/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

export const useProductPageData = (slug?: string) => {
  const productQuery = useQuery<CatalogProduct, Error>({
    queryKey: catalogQueryKeys.publicProduct(slug ?? null),
    queryFn: ({ signal }) => fetchPublicProduct(slug ?? '', { signal }),
    enabled: Boolean(slug),
  });
  const product = productQuery.data ?? null;
  const colorVariantsQuery = useQuery<CatalogProduct[], Error>({
    queryKey: catalogQueryKeys.publicProductColorVariants(product?.slug ?? null),
    queryFn: ({ signal }) =>
      fetchPublicProducts(omitUndefinedProperties({
        category: product?.category.slug,
        sellingType: product?.sellingType,
        sort: 'release_year_desc' as CatalogSort,
        perPage: 100,
        signal,
      })),
    enabled: Boolean(product),
  });
  const colorVariants = useMemo(() => {
    if (!product) return [];
    const variants = (colorVariantsQuery.data ?? []).filter(
      (item) => buildVariantGroupKey(item) === buildVariantGroupKey(product),
    );
    return variants.length > 0 ? variants : [product];
  }, [colorVariantsQuery.data, product]);

  return {
    product,
    colorVariants,
    loading: productQuery.isLoading,
    error: productQuery.error?.message ?? null,
  };
};
