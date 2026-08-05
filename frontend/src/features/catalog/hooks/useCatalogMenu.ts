import { useQuery } from '@tanstack/react-query';

import { fetchPublicCategories, type CatalogCategory } from '../api';
import { catalogQueryKeys } from '@/shared/lib/queryKeys';

type MenuState = 'idle' | 'loading' | 'ready' | 'error';

export const useCatalogMenu = () => {
  const query = useQuery<CatalogCategory[], Error>({
    queryKey: catalogQueryKeys.publicCategories(),
    queryFn: fetchPublicCategories,
  });
  const status: MenuState = query.isLoading ? 'loading' : query.isError ? 'error' : 'ready';

  return {
    categories: query.data ?? [],
    status,
    error: query.error?.message ?? null,
  };
};
