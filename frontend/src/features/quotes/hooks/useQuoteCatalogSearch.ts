import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchPublicProducts, type CatalogProduct } from '@/features/catalog/publicApi';
import { searchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';

export const useQuoteCatalogSearch = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const debouncedQuery = useDebounce(searchQuery.trim(), 300);
  const servicesQuery = useQuery({
    queryKey: quoteQueryKeys.publicServicesSearch(debouncedQuery, 1, 20),
    queryFn: ({ signal }) =>
      searchPublicQuoteServices(omitUndefinedProperties({
        page: 1,
        perPage: 20,
        q: debouncedQuery || undefined,
        signal,
      })),
    enabled: debouncedQuery.length >= 2,
  });
  const productsQuery = useQuery<CatalogProduct[], Error>({
    queryKey: quoteQueryKeys.catalogProducts(debouncedQuery),
    queryFn: ({ signal }) =>
      fetchPublicProducts({ q: debouncedQuery, perPage: 48, sort: 'relevance', signal }),
    enabled: debouncedQuery.length >= 2,
  });
  const allServices = debouncedQuery.length >= 2 ? (servicesQuery.data?.items ?? []) : [];
  const products = debouncedQuery.length >= 2 ? (productsQuery.data ?? []) : [];

  return {
    searchQuery,
    setSearchQuery,
    products,
    productLoading: productsQuery.isFetching,
    allServices,
    filteredServices: allServices,
  };
};
