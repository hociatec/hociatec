import { useEffect, useState } from 'react';
import { fetchPublicQuoteServices, type QuoteServiceDto } from '../api/quotesApi';

export const usePublicQuoteServices = () => {
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => { void fetchPublicQuoteServices().then(setServices).catch((reason: Error) => setError(reason.message || 'Impossible de charger les services.')).finally(() => setLoading(false)); }, []);
  return { services, loading, error };
};
