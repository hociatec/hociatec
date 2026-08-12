import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { searchPublicProducts, type CatalogProduct, type CatalogSort } from '@/features/catalog/publicApi';
import { searchPublicQuoteServices } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import { fetchNewsArticles, type NewsArticleDto } from '@/features/news/publicApi';
import {
  searchPublicTrainings,
  type TrainingDto,
} from '@/features/trainings/publicApi';
import { searchQueryKeys } from '@/features/search/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

interface GlobalSearchState {
  products: CatalogProduct[];
  productTotal: number;
  services: QuoteServiceDto[];
  serviceTotal: number;
  trainings: TrainingDto[];
  trainingTotal: number;
  news: NewsArticleDto[];
  newsTotal: number;
  loading: boolean;
  error: string | null;
}

const initialState: GlobalSearchState = {
  products: [],
  productTotal: 0,
  services: [],
  serviceTotal: 0,
  trainings: [],
  trainingTotal: 0,
  news: [],
  newsTotal: 0,
  loading: false,
  error: null,
};

export const useGlobalSearch = (query: string, limit = 6): GlobalSearchState => {
  const globalQuery = useQuery({
    queryKey: searchQueryKeys.global(query, limit),
    queryFn: async ({ signal }) => {
      const [productResult, serviceResult, trainingResult, newsResult] = await Promise.all([
        searchPublicProducts(omitUndefinedProperties({
          q: query || undefined,
          page: 1,
          perPage: limit,
          sort: (query ? 'relevance' : 'created_desc') as CatalogSort,
          signal,
        })),
        searchPublicQuoteServices(omitUndefinedProperties({ q: query || undefined, page: 1, perPage: limit, signal })),
        searchPublicTrainings(omitUndefinedProperties({ q: query || undefined, page: 1, perPage: limit, signal })),
        fetchNewsArticles(omitUndefinedProperties({ q: query || undefined, page: 1, perPage: limit, signal })),
      ]);

      return { productResult, serviceResult, trainingResult, newsResult };
    },
  });

  return useMemo(() => {
    if (!globalQuery.data) {
      return {
        ...initialState,
        loading: globalQuery.isLoading,
        error: globalQuery.error
          ? globalQuery.error.message || 'Impossible de charger les résultats.'
          : null,
      };
    }

    const { productResult, serviceResult, trainingResult, newsResult } = globalQuery.data;

    return {
      products: productResult.items,
      productTotal: productResult.meta.total,
      services: serviceResult.items,
      serviceTotal: serviceResult.meta.total,
      trainings: trainingResult.items,
      trainingTotal: trainingResult.meta.total,
      news: newsResult.items,
      newsTotal: newsResult.meta.total,
      loading: globalQuery.isLoading,
      error: null,
    };
  }, [globalQuery.data, globalQuery.error, globalQuery.isLoading, limit, query]);
};
