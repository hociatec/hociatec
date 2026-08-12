import { httpClient, requestSignalConfig } from '@/shared/lib/httpClient';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
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

export const searchPublicTrainings = async (
  params: {
    q?: string;
    category?: string;
    format?: string;
    sort?: string;
    minPrice?: number;
    maxPrice?: number;
    minDuration?: number;
    maxDuration?: number;
    page?: number;
    perPage?: number;
    signal?: AbortSignal;
  } = {},
): Promise<PaginatedResult<TrainingDto>> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingDto[]; meta: PaginationMeta }>>(
      TRAINING_API_ROUTES.publicList,
      {
        params: {
          q: params.q,
          category: params.category,
          format: params.format,
          sort: params.sort,
          minPrice: params.minPrice,
          maxPrice: params.maxPrice,
          minDuration: params.minDuration,
          maxDuration: params.maxDuration,
          page: params.page ?? 1,
          perPage: params.perPage ?? 10,
        },
        ...requestSignalConfig(params.signal),
      },
    );
    const data = unwrapTrainingData(res.data);

    return { items: data.items, meta: data.meta };
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
