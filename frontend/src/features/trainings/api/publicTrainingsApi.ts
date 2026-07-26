import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import { TRAINING_API_ROUTES, unwrapTrainingData } from './trainingApiShared';
import type { TrainingCategoryDto, TrainingDto, TrainingSessionDto } from './trainingTypes';

export const fetchPublicTrainings = async (category?: string): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>(TRAINING_API_ROUTES.publicList, {
    params: category ? { category } : undefined,
  });
  return unwrapTrainingData(res.data).items;
};

export const fetchPublicTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    TRAINING_API_ROUTES.publicCategories,
  );
  return unwrapTrainingData(res.data).items;
};

export const fetchPublicTraining = async (
  slug: string,
): Promise<{ training: TrainingDto; sessions: TrainingSessionDto[] }> => {
  const res = await httpClient.get<
    ApiResponse<{ training: TrainingDto; sessions: TrainingSessionDto[] }>
  >(TRAINING_API_ROUTES.publicDetail(slug));
  return unwrapTrainingData(res.data);
};
