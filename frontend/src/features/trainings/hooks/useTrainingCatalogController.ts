import { useMemo } from 'react';
import { useSearchParams } from 'react-router';

import { usePublicTrainingsCatalogData } from '@/features/trainings/hooks/usePublicTrainingsCatalogData';
import type { TrainingFormat } from '@/features/trainings/api/trainingTypes';
import { formatEuroCentsRange } from '@/shared/lib/formatters';
import {
  filterAndSortTrainings,
  getActiveTrainingCategories,
  normalizeTrainingParam,
  normalizeTrainingSort,
  toNullableNumber,
  TRAINING_CATALOG_ALL as ALL,
  TRAINING_CATALOG_PER_PAGE as PER_PAGE,
} from '../lib/trainingCatalog';

export const useTrainingCatalogController = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const { trainings, categories, loading, error } = usePublicTrainingsCatalogData();
  const category = normalizeTrainingParam(searchParams.get('category'));
  const query = searchParams.get('q')?.trim() ?? '';
  const format = normalizeTrainingParam(searchParams.get('format'));
  const sort = normalizeTrainingSort(searchParams.get('sort'));
  const minPrice = toNullableNumber(searchParams.get('minPrice'));
  const maxPrice = toNullableNumber(searchParams.get('maxPrice'));
  const minDuration = toNullableNumber(searchParams.get('minDuration'));
  const maxDuration = toNullableNumber(searchParams.get('maxDuration'));
  const page = Math.max(1, toNullableNumber(searchParams.get('page')) ?? 1);

  const availableCategories = useMemo(() => getActiveTrainingCategories(categories, trainings), [categories, trainings]);
  const categoryOptions = useMemo(() => [
    { value: ALL, label: 'Toutes les catégories' },
    ...availableCategories.map((item) => ({ value: item.slug, label: `${item.name} (${trainings.filter((training) => training.category === item.slug).length})` })),
  ], [availableCategories, trainings]);
  const formatOptions = useMemo(() => {
    const formats = new Map<string, string>();
    trainings.forEach((training) => training.availableFormatDetails.forEach(({ value, label }) => formats.set(value, label)));

    return [
      { value: ALL, label: 'Tous les formats' },
      ...Array.from(formats, ([value, label]) => ({
        value,
        label: `${label} (${trainings.filter((training) => training.availableFormats.includes(value as TrainingFormat)).length})`,
      })),
    ];
  }, [trainings]);
  const categoryName = (slug: string) =>
    trainings.find((training) => training.category === slug)?.categoryDetails?.name ?? '';
  const filteredTrainings = useMemo(() => filterAndSortTrainings(trainings, { category, format, query, sort, minPrice, maxPrice, minDuration, maxDuration }, categoryName), [category, format, maxDuration, maxPrice, minDuration, minPrice, query, sort, trainings, categories]);

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
  const totalPages = Math.max(1, Math.ceil(filteredTrainings.length / PER_PAGE));
  const currentPage = Math.min(page, totalPages);
  const paginatedTrainings = filteredTrainings.slice((currentPage - 1) * PER_PAGE, currentPage * PER_PAGE);
  const resultSummary = query ? `${filteredTrainings.length} formation${filteredTrainings.length > 1 ? 's' : ''} pour "${query}"` : `${filteredTrainings.length} formation${filteredTrainings.length > 1 ? 's' : ''} affichée${filteredTrainings.length > 1 ? 's' : ''}`;
  const priceValues = trainings.map((training) => training.priceCents);
  const durationValues = trainings.map((training) => training.durationMinutes);
  const priceHint = priceValues.length > 0 ? formatEuroCentsRange(Math.min(...priceValues), Math.max(...priceValues)) : null;
  const durationHint = durationValues.length > 0 ? `${Math.min(...durationValues)} à ${Math.max(...durationValues)} min` : null;

  return { trainings, loading, error, category, format, sort, minPrice, maxPrice, minDuration, maxDuration, categoryOptions, formatOptions, priceHint, durationHint, resultSummary, paginatedTrainings, categoryName, currentPage, totalPages, updateParam, updateRange, resetFilters };
};
