import { useQuery } from '@tanstack/react-query';

import {
  fetchPublicTrainingCategories,
  fetchPublicTrainings,
  type TrainingCategoryDto,
  type TrainingDto,
} from '@/features/trainings/api/trainingsApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { trainingQueryKeys } from '@/shared/lib/queryKeys';

export const usePublicTrainingsCatalogData = () => {
  const query = useQuery<{ trainings: TrainingDto[]; categories: TrainingCategoryDto[] }, Error>({
    queryKey: trainingQueryKeys.publicCatalog(),
    queryFn: async ({ signal }) => {
      const [trainings, categories] = await Promise.all([
        fetchPublicTrainings(undefined, { signal }),
        fetchPublicTrainingCategories({ signal }),
      ]);

      return { trainings, categories };
    },
  });

  return {
    trainings: query.data?.trainings ?? [],
    categories: query.data?.categories ?? [],
    loading: query.isLoading,
    error: query.error ? getHttpErrorMessage(query.error, 'Impossible de charger les formations.') : null,
    retry: query.refetch,
  };
};
