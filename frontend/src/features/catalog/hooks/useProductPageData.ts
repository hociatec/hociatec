import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import {
  fetchPublicProduct,
  fetchPublicProductVariants,
  fetchPublicProducts,
  type CatalogProduct,
} from '../api';
import { catalogQueryKeys } from '@/features/catalog/queryKeys';
import { buildVariantGroupKey } from '@/features/catalog/utils/productPageDisplay';

export const useProductPageData = (slug?: string) => {
  const productQuery = useQuery<CatalogProduct, Error>({
    queryKey: catalogQueryKeys.publicProduct(slug ?? null),
    queryFn: ({ signal }) => fetchPublicProduct(slug ?? '', { signal }),
    enabled: Boolean(slug),
  });
  const product = productQuery.data ?? null;
  const variantsQuery = useQuery<CatalogProduct[], Error>({
    queryKey: catalogQueryKeys.publicProductVariants(product?.slug ?? null),
    queryFn: ({ signal }) =>
      fetchPublicProductVariants(product?.slug ?? '', { signal }),
    enabled: Boolean(product),
  });
  const fallbackVariantsQuery = useQuery<CatalogProduct[], Error>({
    queryKey: product
      ? catalogQueryKeys.productVariants(
          product.category.slug,
          product.sellingType,
          buildVariantGroupKey(product),
        )
      : ['catalog', 'product-variants-fallback', null],
    queryFn: async ({ signal }) => {
      if (!product) return [];

      const products = await fetchPublicProducts({
        category: product.category.slug,
        sellingType: product.sellingType,
        perPage: 100,
        signal,
      });
      const currentGroupKey = buildVariantGroupKey(product);

      return products.filter((item) => buildVariantGroupKey(item) === currentGroupKey);
    },
    enabled: Boolean(product),
  });
  const colorVariants = useMemo(
    () => {
      if (!product) return [];
      if (variantsQuery.data && variantsQuery.data.length > 1) return variantsQuery.data;
      if (fallbackVariantsQuery.data && fallbackVariantsQuery.data.length > 1) {
        return fallbackVariantsQuery.data;
      }
      if (variantsQuery.data && variantsQuery.data.length > 0) return variantsQuery.data;
      return [product];
    },
    [fallbackVariantsQuery.data, product, variantsQuery.data],
  );

  return {
    product,
    colorVariants,
    loading: productQuery.isLoading,
    error: productQuery.error?.message ?? null,
  };
};
