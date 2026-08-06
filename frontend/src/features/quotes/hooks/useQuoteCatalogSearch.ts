import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/publicApi';
import { fetchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';

export const useQuoteCatalogSearch = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const debouncedQuery = useDebounce(searchQuery.trim(), 300);
  const servicesQuery = useQuery<QuoteServiceDto[], Error>({
    queryKey: quoteQueryKeys.publicServices(),
    queryFn: ({ signal }) => fetchPublicQuoteServices({ signal }),
  });
  const productsQuery = useQuery<CatalogProduct[], Error>({
    queryKey: quoteQueryKeys.catalogProducts(debouncedQuery),
    queryFn: ({ signal }) =>
      fetchPublicProducts({ q: debouncedQuery, perPage: 48, sort: 'relevance', signal }),
    enabled: debouncedQuery.length >= 2,
  });
  const allServices = servicesQuery.data ?? [];
  const products = debouncedQuery.length >= 2 ? (productsQuery.data ?? []) : [];

  const filteredServices = useMemo(
    () => allServices.filter((service) => service.title.toLowerCase().includes(searchQuery.trim().toLowerCase())).slice(0, 20),
    [allServices, searchQuery],
  );

  return {
    searchQuery,
    setSearchQuery,
    products,
    productLoading: productsQuery.isFetching,
    allServices,
    filteredServices,
  };
};
