import type { TrainingCategoryDto, TrainingDto } from '../api/trainingsApi';
import type { TrainingFormat } from '../api/trainingTypes';

import { parseNullableNonNegativeInteger } from '@/shared/lib/parsers';
import { normalizeSearchText } from '@/shared/lib/searchText';

export const TRAINING_CATALOG_ALL = 'all';
export const TRAINING_CATALOG_PER_PAGE = 10;
export type TrainingSort =
  'title_asc' | 'price_asc' | 'price_desc' | 'duration_asc' | 'duration_desc';
export const formatTrainingDuration = (minutes: number) => {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  return hours > 0 ? `${hours}h${rest ? String(rest).padStart(2, '0') : ''}` : `${minutes} min`;
};
export const formatTrainingDelivery = (formats: TrainingFormat[]) => {
  const hasOnsite = formats.includes('onsite');
  const hasRemote = formats.includes('remote');

  if (hasOnsite && hasRemote) {
    return 'Présentiel et distanciel';
  }

  if (hasOnsite) {
    return 'Présentiel';
  }

  if (hasRemote) {
    return 'Distanciel';
  }

  return 'Modalité à confirmer';
};
export const toNullableNumber = (value: string | null) => parseNullableNonNegativeInteger(value);
export const normalizeTrainingParam = (value: string | null) =>
  value && value.trim() ? value : TRAINING_CATALOG_ALL;
export const normalizeTrainingSearch = (value: string | null | undefined) =>
  normalizeSearchText(value);
export const normalizeTrainingSort = (value: string | null): TrainingSort => {
  const allowed: TrainingSort[] = [
    'title_asc',
    'price_asc',
    'price_desc',
    'duration_asc',
    'duration_desc',
  ];
  return allowed.includes(value as TrainingSort) ? (value as TrainingSort) : 'title_asc';
};
export const filterAndSortTrainings = (
  trainings: TrainingDto[],
  filters: {
    category: string;
    format: string;
    query: string;
    sort: TrainingSort;
    minPrice: number | null;
    maxPrice: number | null;
    minDuration: number | null;
    maxDuration: number | null;
  },
  categoryName: (slug: string) => string,
) => {
  const normalizedQuery = normalizeTrainingSearch(filters.query);
  return trainings
    .filter((training) => {
      const price = training.priceCents / 100;
      const matchesQuery =
        !normalizedQuery ||
        [
          training.title,
          training.shortDescription,
          training.objective,
          training.audience,
          training.categoryDetails?.name ?? categoryName(training.category),
        ].some((value) => normalizeTrainingSearch(value).includes(normalizedQuery));
      return (
        matchesQuery &&
        (filters.category === TRAINING_CATALOG_ALL || training.category === filters.category) &&
        (filters.format === TRAINING_CATALOG_ALL ||
          training.availableFormats.includes(filters.format as 'onsite' | 'remote')) &&
        (filters.minPrice === null || price >= filters.minPrice) &&
        (filters.maxPrice === null || price <= filters.maxPrice) &&
        (filters.minDuration === null || training.durationMinutes >= filters.minDuration) &&
        (filters.maxDuration === null || training.durationMinutes <= filters.maxDuration)
      );
    })
    .sort((left, right) =>
      filters.sort === 'price_asc'
        ? left.priceCents - right.priceCents
        : filters.sort === 'price_desc'
          ? right.priceCents - left.priceCents
          : filters.sort === 'duration_asc'
            ? left.durationMinutes - right.durationMinutes
            : filters.sort === 'duration_desc'
              ? right.durationMinutes - left.durationMinutes
              : left.title.localeCompare(right.title, 'fr'),
    );
};
export const getActiveTrainingCategories = (
  categories: TrainingCategoryDto[],
  trainings: TrainingDto[],
) =>
  categories.filter(
    (item) => item.isActive && trainings.some((training) => training.category === item.slug),
  );
