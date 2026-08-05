import { httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import { TRAINING_API_ROUTES, trainingRequest, unwrapTrainingData } from './trainingApiShared';
import type { TrainingCategoryDto, TrainingDto, TrainingSessionDto } from './trainingTypes';

type RequestOptions = {
  signal?: AbortSignal;
};

export const fetchPublicTrainings = async (
  category?: string,
  options: RequestOptions = {},
): Promise<TrainingDto[]> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>(
      TRAINING_API_ROUTES.publicList,
      {
        ...(category ? { params: { category } } : {}),
        ...requestSignalConfig(options.signal),
      },
    );
    return unwrapTrainingData(res.data).items;
  }, 'Impossible de charger les formations.');
};

export const fetchPublicTrainingCategories = async (
  options: RequestOptions = {},
): Promise<TrainingCategoryDto[]> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
      TRAINING_API_ROUTES.publicCategories,
      requestSignalConfig(options.signal),
    );
    return unwrapTrainingData(res.data).items;
  }, 'Impossible de charger les catégories de formation.');
};

export const fetchPublicTraining = async (
  slug: string,
  options: RequestOptions = {},
): Promise<{ training: TrainingDto; sessions: TrainingSessionDto[] }> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<
      ApiResponse<{ training: TrainingDto; sessions: TrainingSessionDto[] }>
    >(TRAINING_API_ROUTES.publicDetail(slug), requestSignalConfig(options.signal));
    return unwrapTrainingData(res.data);
  }, 'Impossible de charger la formation.');
};
