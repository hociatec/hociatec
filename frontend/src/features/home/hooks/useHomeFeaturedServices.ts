import { useEffect, useState } from 'react';

import { fetchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import { selectFeaturedServices } from '@/features/quotes/lib/servicePresentation';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const HOMEPAGE_SERVICE_LIMIT = 6;

export const useHomeFeaturedServices = () => {
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);

    void fetchPublicQuoteServices()
      .then((items) => {
        if (cancelled) {
          return;
        }
        setServices(selectFeaturedServices(items, HOMEPAGE_SERVICE_LIMIT));
      })
      .catch((reason) => {
        if (!cancelled) {
          setError(getHttpErrorMessage(reason, 'Impossible de charger les services.'));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return { services, loading, error };
};
