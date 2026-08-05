import { useEffect, useState } from 'react';
import { fetchPublicQuoteServices } from '../api/quotesApi';
import type { QuoteServiceDto } from '../types/quoteTypes';

export const usePublicQuoteServices = () => {
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    const controller = new AbortController();
    void fetchPublicQuoteServices({ signal: controller.signal })
      .then(setServices)
      .catch((reason: Error) => {
        if (!controller.signal.aborted)
          setError(reason.message || 'Impossible de charger les services.');
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });

    return () => {
      controller.abort();
    };
  }, []);
  return { services, loading, error };
};
