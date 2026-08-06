import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router';
import { fetchPublicQuoteService } from '../api/quotesApi';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';

export const usePublicServiceDetail = () => {
  const { serviceId: rawServiceId } = useParams<{ serviceId: string }>();
  const serviceId = Number.parseInt(rawServiceId ?? '', 10);
  const query = useQuery({
    queryKey: quoteQueryKeys.publicService(Number.isFinite(serviceId) ? serviceId : null),
    queryFn: ({ signal }) => fetchPublicQuoteService(serviceId, { signal }),
    enabled: Number.isFinite(serviceId),
  });

  return {
    serviceId,
    service: query.data ?? null,
    loading: query.isLoading,
    error: !Number.isFinite(serviceId)
      ? 'Service introuvable.'
      : query.error instanceof Error
        ? query.error.message || 'Impossible de charger ce service.'
        : null,
    retry: query.refetch,
  };
};
