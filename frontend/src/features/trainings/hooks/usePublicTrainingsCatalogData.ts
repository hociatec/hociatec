import { useEffect, useState } from 'react';

import {
  fetchPublicTrainingCategories,
  fetchPublicTrainings,
  type TrainingCategoryDto,
  type TrainingDto,
} from '@/features/trainings/api/trainingsApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const usePublicTrainingsCatalogData = () => {
  const [trainings, setTrainings] = useState<TrainingDto[]>([]);
  const [categories, setCategories] = useState<TrainingCategoryDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    void Promise.all([fetchPublicTrainings(), fetchPublicTrainingCategories()])
      .then(([trainingItems, categoryItems]) => {
        if (cancelled) return;
        setTrainings(trainingItems);
        setCategories(categoryItems);
      })
      .catch((reason) => {
        if (!cancelled)
          setError(getHttpErrorMessage(reason, 'Impossible de charger les formations.'));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return { trainings, categories, loading, error };
};
