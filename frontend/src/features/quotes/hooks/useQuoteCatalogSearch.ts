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
    void fetchPublicQuoteServices().then(setAllServices).catch(() => void 0);
  }, []);

  useEffect(() => {
    const query = searchQuery.trim();
    if (productDebounce.current) window.clearTimeout(productDebounce.current);
    if (query.length < 2) {
      setProducts([]);
      setProductLoading(false);
      return;
    }
    setProductLoading(true);
    productDebounce.current = window.setTimeout(() => {
      void fetchPublicProducts({ q: query, perPage: 48, sort: 'relevance' })
        .then(setProducts)
        .finally(() => setProductLoading(false));
    }, 300);
  }, [searchQuery]);

  const filteredServices = useMemo(
    () => allServices.filter((service) => service.title.toLowerCase().includes(searchQuery.trim().toLowerCase())).slice(0, 20),
    [allServices, searchQuery],
  );

  return { searchQuery, setSearchQuery, products, productLoading, allServices, filteredServices };
};
