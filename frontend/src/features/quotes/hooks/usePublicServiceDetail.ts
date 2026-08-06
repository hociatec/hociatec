import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router';
import { fetchPublicQuoteService } from '../api/quotesApi';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const usePublicServiceDetail = () => {
  const { serviceId: rawServiceId } = useParams<{ serviceId: string }>();
  const serviceId = parseNullablePositiveInteger(rawServiceId);
  const query = useQuery({
    queryKey: quoteQueryKeys.publicService(serviceId),
    queryFn: ({ signal }) => fetchPublicQuoteService(serviceId!, { signal }),
    enabled: serviceId !== null,
  });

  return {
    serviceId,
    service: query.data ?? null,
    loading: query.isLoading,
    error: serviceId === null
      ? 'Service introuvable.'
      : query.error instanceof Error
        ? query.error.message || 'Impossible de charger ce service.'
        : null,
    retry: query.refetch,
  };
};
