import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';

import { searchPublicProducts, type CatalogProduct, type CatalogSort } from '@/features/catalog/publicApi';
import { fetchPublicQuoteServices } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import { fetchNewsArticles, type NewsArticleDto } from '@/features/news/publicApi';
import {
  fetchPublicTrainings,
  type TrainingDto,
} from '@/features/trainings/publicApi';
import { searchQueryKeys } from '@/features/search/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

const normalize = (value: string | null | undefined) =>
  (value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const matches = (query: string, fields: Array<string | null | undefined>) => {
  const normalizedQuery = normalize(query);
  return !normalizedQuery || fields.some((field) => normalize(field).includes(normalizedQuery));
};

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
      const [productResult, serviceItems, trainingItems, newsResult] = await Promise.all([
        searchPublicProducts(omitUndefinedProperties({
          q: query || undefined,
          page: 1,
          perPage: limit,
          sort: (query ? 'relevance' : 'created_desc') as CatalogSort,
          signal,
        })),
        fetchPublicQuoteServices({ signal }),
        fetchPublicTrainings(undefined, { signal }),
        fetchNewsArticles(omitUndefinedProperties({ q: query || undefined, page: 1, perPage: limit, signal })),
      ]);

      return { productResult, serviceItems, trainingItems, newsResult };
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

    const { productResult, serviceItems, trainingItems, newsResult } = globalQuery.data;
    const filteredServices = serviceItems.filter((service) =>
      matches(query, [service.title, service.description, service.unit, service.durationLabel]),
    );
    const filteredTrainings = trainingItems.filter((training) =>
      matches(query, [
        training.title,
        training.shortDescription,
        training.objective,
        training.audience,
        training.categoryDetails?.name,
      ]),
    );

    return {
      products: productResult.items,
      productTotal: productResult.meta.total,
      services: filteredServices.slice(0, limit),
      serviceTotal: filteredServices.length,
      trainings: filteredTrainings.slice(0, limit),
      trainingTotal: filteredTrainings.length,
      news: newsResult.items,
      newsTotal: newsResult.meta.total,
      loading: globalQuery.isLoading,
      error: null,
    };
  }, [globalQuery.data, globalQuery.error, globalQuery.isLoading, limit, query]);
};
