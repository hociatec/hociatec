import { useEffect, useState } from 'react';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const useHomeFeaturedProducts = () => {
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    void fetchPublicProducts({ homepage: true })
      .then((items) => {
        if (!cancelled) setProducts(items);
      })
      .catch((reason) => {
        if (!cancelled)
          setError(getHttpErrorMessage(reason, 'Impossible de charger les produits.'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return { products, loading, error };
};
