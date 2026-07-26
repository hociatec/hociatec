import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import { TRAINING_API_ROUTES, trainingRequest, unwrapTrainingData } from './trainingApiShared';
import type { TrainingCategoryDto, TrainingDto, TrainingSessionDto } from './trainingTypes';

export const fetchPublicTrainings = async (category?: string): Promise<TrainingDto[]> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>(
      TRAINING_API_ROUTES.publicList,
      { params: category ? { category } : undefined },
    );
    return unwrapTrainingData(res.data).items;
  }, 'Impossible de charger les formations.');
};

export const fetchPublicTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
      TRAINING_API_ROUTES.publicCategories,
    );
    return unwrapTrainingData(res.data).items;
  }, 'Impossible de charger les catégories de formation.');
};

export const fetchPublicTraining = async (
  slug: string,
): Promise<{ training: TrainingDto; sessions: TrainingSessionDto[] }> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<
      ApiResponse<{ training: TrainingDto; sessions: TrainingSessionDto[] }>
    >(TRAINING_API_ROUTES.publicDetail(slug));
    return unwrapTrainingData(res.data);
  }, 'Impossible de charger la formation.');
};
