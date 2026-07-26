import { useEffect, useState } from 'react';

import { searchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { fetchPublicQuoteServices } from '@/features/quotes/api/quotesApi';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import {
  fetchPublicTrainings,
  type TrainingDto,
} from '@/features/trainings/api/trainingsApi';

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
  loading: false,
  error: null,
};

export const useGlobalSearch = (query: string, limit = 6): GlobalSearchState => {
  const [state, setState] = useState<GlobalSearchState>(initialState);

  useEffect(() => {
    let cancelled = false;
    setState((current) => ({ ...current, loading: true, error: null }));

    const loadResults = async () => {
      try {
        const [productResult, serviceItems, trainingItems] = await Promise.all([
          searchPublicProducts({
            q: query || undefined,
            page: 1,
            perPage: limit,
            sort: query ? 'relevance' : 'created_desc',
          }),
          fetchPublicQuoteServices(),
          fetchPublicTrainings(),
        ]);

        if (cancelled) return;

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

        setState({
          products: productResult.items,
          productTotal: productResult.meta.total,
          services: filteredServices.slice(0, limit),
          serviceTotal: filteredServices.length,
          trainings: filteredTrainings.slice(0, limit),
          trainingTotal: filteredTrainings.length,
          loading: false,
          error: null,
        });
      } catch (reason) {
        if (!cancelled) {
          setState((current) => ({
            ...current,
            loading: false,
            error:
              reason instanceof Error ? reason.message : 'Impossible de charger les résultats.',
          }));
        }
      }
    };

    void loadResults();
    return () => {
      cancelled = true;
    };
  }, [limit, query]);

  return state;
};
