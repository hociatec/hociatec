import { useMemo } from 'react';
import { useSearchParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';

import { formatEuroCentsRange } from '@/shared/lib/formatters';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { fetchPublicTrainingCategories, searchPublicTrainings } from '@/features/trainings/api/trainingsApi';
import { trainingQueryKeys } from '@/features/trainings/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';
import {
  normalizeTrainingParam,
  normalizeTrainingSort,
  toNullableNumber,
  TRAINING_CATALOG_ALL as ALL,
  TRAINING_CATALOG_PER_PAGE as PER_PAGE,
} from '../lib/trainingCatalog';

export const useTrainingCatalogController = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const category = normalizeTrainingParam(searchParams.get('category'));
  const query = searchParams.get('q')?.trim() ?? '';
  const format = normalizeTrainingParam(searchParams.get('format'));
  const sort = normalizeTrainingSort(searchParams.get('sort'));
  const minPrice = toNullableNumber(searchParams.get('minPrice'));
  const maxPrice = toNullableNumber(searchParams.get('maxPrice'));
  const minDuration = toNullableNumber(searchParams.get('minDuration'));
  const maxDuration = toNullableNumber(searchParams.get('maxDuration'));
  const page = parseNullablePositiveInteger(searchParams.get('page')) ?? 1;
  const catalogQuery = useQuery({
    queryKey: trainingQueryKeys.publicCatalogSearch({
      category,
      format,
      maxDuration,
      maxPrice,
      minDuration,
      minPrice,
      page,
      query,
      sort,
    }),
    queryFn: async ({ signal }) => {
      const [result, categories] = await Promise.all([
        searchPublicTrainings(omitUndefinedProperties({
          q: query || undefined,
          category: category === ALL ? undefined : category,
          format: format === ALL ? undefined : format,
          sort,
          minPrice: minPrice ?? undefined,
          maxPrice: maxPrice ?? undefined,
          minDuration: minDuration ?? undefined,
          maxDuration: maxDuration ?? undefined,
          page,
          perPage: PER_PAGE,
          signal,
        })),
        fetchPublicTrainingCategories({ signal }),
      ]);

      return { result, categories };
    },
  });
  const trainings = catalogQuery.data?.result.items ?? [];
  const categories = catalogQuery.data?.categories ?? [];
  const meta = catalogQuery.data?.result.meta ?? { page, perPage: PER_PAGE, total: 0, totalPages: 1 };

  const availableCategories = useMemo(() => categories.filter((item) => item.isActive), [categories]);
  const categoryOptions = useMemo(() => [
    { value: ALL, label: 'Toutes les catégories' },
    ...availableCategories.map((item) => ({ value: item.slug, label: item.name })),
  ], [availableCategories]);
  const formatOptions = useMemo(() => {
    return [
      { value: ALL, label: 'Tous les formats' },
      { value: 'onsite', label: 'Présentiel' },
      { value: 'remote', label: 'Distanciel' },
    ];
  }, []);

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams);
    if (value === null || value === '' || value === ALL) next.delete(key); else next.set(key, value);
    if (key !== 'page') next.delete('page');
    setSearchParams(next, { replace: true });
  };
  const updateRange = (minKey: string, maxKey: string, nextRange: { min: number | null; max: number | null }) => {
    const next = new URLSearchParams(searchParams);
    if (nextRange.min === null) next.delete(minKey); else next.set(minKey, String(nextRange.min));
    if (nextRange.max === null) next.delete(maxKey); else next.set(maxKey, String(nextRange.max));
    next.delete('page');
    setSearchParams(next, { replace: true });
  };
  const resetFilters = () => setSearchParams(new URLSearchParams(), { replace: true });
  const totalPages = clampAtLeast(meta.totalPages, 1);
  const currentPage = clampWithin(meta.page, 1, totalPages);
  const paginatedTrainings = trainings;
  const resultSummary = query ? `${meta.total} formation${meta.total > 1 ? 's' : ''} pour "${query}"` : `${meta.total} formation${meta.total > 1 ? 's' : ''} affichée${meta.total > 1 ? 's' : ''}`;
  const priceValues = trainings.map((training) => training.priceCents);
  const durationValues = trainings.map((training) => training.durationMinutes);
  const priceHint = priceValues.length > 0 ? formatEuroCentsRange(Math.min(...priceValues), Math.max(...priceValues)) : null;
  const durationHint = durationValues.length > 0 ? `${Math.min(...durationValues)} à ${Math.max(...durationValues)} min` : null;

  return {
    trainings,
    total: meta.total,
    loading: catalogQuery.isLoading,
    error: catalogQuery.error
      ? getHttpErrorMessage(catalogQuery.error, 'Impossible de charger les formations.')
      : null,
    retry: catalogQuery.refetch,
    category,
    format,
    sort,
    minPrice,
    maxPrice,
    minDuration,
    maxDuration,
    categoryOptions,
    formatOptions,
    priceHint,
    durationHint,
    resultSummary,
    paginatedTrainings,
    currentPage,
    totalPages,
    updateParam,
    updateRange,
    resetFilters,
  };
};
