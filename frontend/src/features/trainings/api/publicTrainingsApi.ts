import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { TrainingCategoryDto, TrainingDto, TrainingSessionDto } from './trainingTypes';

const unwrapData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') throw new Error(response.message);
  return response.data;
};

export const fetchPublicTrainings = async (category?: string): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>('/api/public/trainings', {
    params: category ? { category } : undefined,
  });
  return unwrapData(res.data).items;
};

export const fetchPublicTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    '/api/public/training-categories',
  );
  return unwrapData(res.data).items;
};

export const fetchPublicTraining = async (
  slug: string,
): Promise<{ training: TrainingDto; sessions: TrainingSessionDto[] }> => {
  const res = await httpClient.get<
    ApiResponse<{ training: TrainingDto; sessions: TrainingSessionDto[] }>
  >(`/api/public/trainings/${encodeURIComponent(slug)}`);
  return unwrapData(res.data);
};
