import { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import { fetchPublicQuoteService } from '../api/quotesApi';
import type { QuoteServiceDto } from '../types/quoteTypes';

export const usePublicServiceDetail = () => {
  const { serviceId: rawServiceId } = useParams<{ serviceId: string }>();
  const serviceId = Number.parseInt(rawServiceId ?? '', 10);
  const [service, setService] = useState<QuoteServiceDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    if (!Number.isFinite(serviceId)) {
      setError('Service introuvable.');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    void fetchPublicQuoteService(serviceId)
      .then(setService)
      .catch((err: Error) => setError(err.message || 'Impossible de charger ce service.'))
      .finally(() => setLoading(false));
  }, [serviceId]);
  return { serviceId, service, loading, error };
};
