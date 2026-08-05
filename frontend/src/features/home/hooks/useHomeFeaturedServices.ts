import { useQuery } from '@tanstack/react-query';

import { fetchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import { selectFeaturedServices } from '@/features/quotes/lib/servicePresentation';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { homeQueryKeys } from '@/shared/lib/queryKeys';

const HOMEPAGE_SERVICE_LIMIT = 6;

export const useHomeFeaturedServices = () => {
  const query = useQuery<QuoteServiceDto[], Error>({
    queryKey: homeQueryKeys.featuredServices(),
    queryFn: ({ signal }) => fetchPublicQuoteServices({ signal }),
    select: (items) => selectFeaturedServices(items, HOMEPAGE_SERVICE_LIMIT),
  });

  return {
    services: query.data ?? [],
    loading: query.isLoading,
    error: query.error ? getHttpErrorMessage(query.error, 'Impossible de charger les services.') : null,
  };
};
