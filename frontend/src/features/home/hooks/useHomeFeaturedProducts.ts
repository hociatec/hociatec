import { useQuery } from '@tanstack/react-query';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { catalogQueryKeys } from '@/features/catalog/queryKeys';

const HOMEPAGE_PRODUCT_LIMIT = 6;

export const useHomeFeaturedProducts = () => {
  const query = useQuery<CatalogProduct[], Error>({
    queryKey: catalogQueryKeys.homeProducts(),
    queryFn: ({ signal }) =>
      fetchPublicProducts({
        homepage: true,
        perPage: HOMEPAGE_PRODUCT_LIMIT,
        signal,
      }),
    staleTime: 10 * 60_000,
    refetchOnWindowFocus: false,
    select: (items) => items.slice(0, HOMEPAGE_PRODUCT_LIMIT),
  });

  return {
    products: query.data ?? [],
    loading: query.isLoading,
    error: query.error ? getHttpErrorMessage(query.error, 'Impossible de charger les produits.') : null,
  };
};
