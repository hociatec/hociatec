import { useEffect, useMemo, useRef, useState } from 'react';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { fetchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';

export const useQuoteCatalogSearch = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [productLoading, setProductLoading] = useState(false);
  const [allServices, setAllServices] = useState<QuoteServiceDto[]>([]);
  const productDebounce = useRef<number | undefined>(undefined);

  useEffect(() => {
    const controller = new AbortController();
    void fetchPublicQuoteServices({ signal: controller.signal })
      .then(setAllServices)
      .catch(() => void 0);

    return () => {
      controller.abort();
    };
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    const query = searchQuery.trim();
    if (productDebounce.current) window.clearTimeout(productDebounce.current);
    if (query.length < 2) {
      setProducts([]);
      setProductLoading(false);
      return;
    }
    setProductLoading(true);
    productDebounce.current = window.setTimeout(() => {
      void fetchPublicProducts({ q: query, perPage: 48, sort: 'relevance', signal: controller.signal })
        .then((items) => {
          if (!controller.signal.aborted) setProducts(items);
        })
        .finally(() => {
          if (!controller.signal.aborted) setProductLoading(false);
        });
    }, 300);

    return () => {
      if (productDebounce.current) window.clearTimeout(productDebounce.current);
      controller.abort();
    };
  }, [searchQuery]);

  const filteredServices = useMemo(
    () => allServices.filter((service) => service.title.toLowerCase().includes(searchQuery.trim().toLowerCase())).slice(0, 20),
    [allServices, searchQuery],
  );

  return { searchQuery, setSearchQuery, products, productLoading, allServices, filteredServices };
};
