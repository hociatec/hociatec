import { useQuery } from '@tanstack/react-query';
import { fetchPublicQuoteServices } from '../api/quotesApi';
import type { QuoteServiceDto } from '../types/quoteTypes';
import { quoteQueryKeys } from '@/shared/lib/queryKeys';

export const usePublicQuoteServices = () => {
  const query = useQuery<QuoteServiceDto[], Error>({
    queryKey: quoteQueryKeys.publicServices(),
    queryFn: ({ signal }) => fetchPublicQuoteServices({ signal }),
  });

  return {
    services: query.data ?? [],
    loading: query.isLoading,
    error: query.error?.message ?? null,
    retry: query.refetch,
  };
};
