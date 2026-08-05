import { useEffect, useState } from 'react';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const HOMEPAGE_PRODUCT_LIMIT = 6;

export const useHomeFeaturedProducts = () => {
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    void fetchPublicProducts({
      homepage: true,
      perPage: HOMEPAGE_PRODUCT_LIMIT,
      signal: controller.signal,
    })
      .then((items) => {
        if (!cancelled) setProducts(items.slice(0, HOMEPAGE_PRODUCT_LIMIT));
      })
      .catch((reason) => {
        if (controller.signal.aborted) return;
        if (!cancelled)
          setError(getHttpErrorMessage(reason, 'Impossible de charger les produits.'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
      controller.abort();
    };
  }, []);

  return { products, loading, error };
};
