import { httpClient } from '@/shared/lib/httpClient';
import type { ApiMutationResult, ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type {
  TrainingCategoryDto,
  TrainingCategoryInput,
  TrainingDto,
  TrainingEnrollmentDto,
  TrainingEnrollmentStatus,
  TrainingInput,
  TrainingSessionDto,
  TrainingSessionInput,
} from './trainingTypes';
import { TRAINING_API_ROUTES, unwrapTrainingData, unwrapTrainingResult } from './trainingApiShared';

export const fetchAdminTrainings = async (): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>(TRAINING_API_ROUTES.adminTrainings, { params: { page: 1, perPage: 100 } });
  return unwrapTrainingData(res.data).items;
};

export const fetchAdminTrainingsPage = async (page = 1, perPage = 10): Promise<PaginatedResult<TrainingDto>> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[]; meta: PaginationMeta }>>(TRAINING_API_ROUTES.adminTrainings, { params: { page, perPage } });
  return unwrapTrainingData(res.data);
};

export const fetchAdminTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    TRAINING_API_ROUTES.adminCategories,
    { params: { page: 1, perPage: 100 } },
  );
  return unwrapTrainingData(res.data).items;
};

export const fetchAdminTrainingCategoriesPage = async (page = 1, perPage = 10): Promise<PaginatedResult<TrainingCategoryDto>> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[]; meta: PaginationMeta }>>(
    TRAINING_API_ROUTES.adminCategories,
    { params: { page, perPage } },
  );
  return unwrapTrainingData(res.data);
};

export const createAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
): Promise<ApiMutationResult<TrainingCategoryDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingCategoryDto>>(
    TRAINING_API_ROUTES.adminCategories,
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const updateAdminTrainingCategory = async (
  id: number,
  payload: TrainingCategoryInput,
): Promise<ApiMutationResult<TrainingCategoryDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingCategoryDto>>(
    TRAINING_API_ROUTES.adminCategory(id),
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const saveAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
  id?: number,
): Promise<ApiMutationResult<TrainingCategoryDto>> =>
  id === undefined
    ? createAdminTrainingCategory(payload)
    : updateAdminTrainingCategory(id, payload);

export const deleteAdminTrainingCategory = async (id: number) => {
  const res = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(TRAINING_API_ROUTES.adminCategory(id));
  return unwrapTrainingResult(res.data);
};

export const fetchAdminTraining = async (id: number): Promise<TrainingDto> => {
  const res = await httpClient.get<ApiResponse<TrainingDto>>(TRAINING_API_ROUTES.adminTraining(id));
  return unwrapTrainingData(res.data);
};

export const createAdminTraining = async (payload: TrainingInput): Promise<ApiMutationResult<TrainingDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingDto>>(
    TRAINING_API_ROUTES.adminTrainings,
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const updateAdminTraining = async (
  id: number,
  payload: TrainingInput,
): Promise<ApiMutationResult<TrainingDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingDto>>(
    TRAINING_API_ROUTES.adminTraining(id),
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const saveAdminTraining = async (
  payload: TrainingInput,
  id?: number,
): Promise<ApiMutationResult<TrainingDto>> =>
  id === undefined ? createAdminTraining(payload) : updateAdminTraining(id, payload);

export const deleteAdminTraining = async (id: number) => {
  const res = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(TRAINING_API_ROUTES.adminTraining(id));
  return unwrapTrainingResult(res.data);
};

export const fetchAdminTrainingSessions = async (): Promise<TrainingSessionDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingSessionDto[] }>>(
    TRAINING_API_ROUTES.adminSessions,
    { params: { page: 1, perPage: 100 } },
  );
  return unwrapTrainingData(res.data).items;
};

export const fetchAdminTrainingSessionsPage = async (page = 1, perPage = 10): Promise<PaginatedResult<TrainingSessionDto>> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingSessionDto[]; meta: PaginationMeta }>>(
    TRAINING_API_ROUTES.adminSessions,
    { params: { page, perPage } },
  );
  return unwrapTrainingData(res.data);
};

export const createAdminTrainingSession = async (
  payload: TrainingSessionInput,
): Promise<ApiMutationResult<TrainingSessionDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingSessionDto>>(
    TRAINING_API_ROUTES.adminSessions,
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const updateAdminTrainingSession = async (
  id: number,
  payload: TrainingSessionInput,
): Promise<ApiMutationResult<TrainingSessionDto>> => {
  const res = await httpClient.post<ApiResponse<TrainingSessionDto>>(
    TRAINING_API_ROUTES.adminSession(id),
    payload,
  );
  return unwrapTrainingResult(res.data);
};

export const saveAdminTrainingSession = async (
  payload: TrainingSessionInput,
  id?: number,
): Promise<ApiMutationResult<TrainingSessionDto>> =>
  id === undefined
    ? createAdminTrainingSession(payload)
    : updateAdminTrainingSession(id, payload);

export const deleteAdminTrainingSession = async (id: number) => {
  const res = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(TRAINING_API_ROUTES.adminSession(id));
  return unwrapTrainingResult(res.data);
};

export const fetchAdminTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    TRAINING_API_ROUTES.adminEnrollments,
    { params: { page: 1, perPage: 100 } },
  );
  return unwrapTrainingData(res.data).items;
};

export const fetchAdminTrainingEnrollmentsPage = async (page = 1, perPage = 10): Promise<PaginatedResult<TrainingEnrollmentDto>> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[]; meta: PaginationMeta }>>(
    TRAINING_API_ROUTES.adminEnrollments,
    { params: { page, perPage } },
  );
  return unwrapTrainingData(res.data);
};

export const updateAdminTrainingEnrollmentStatus = async (
  id: number,
  status: TrainingEnrollmentStatus,
): Promise<TrainingEnrollmentDto> => {
  const res = await httpClient.patch<ApiResponse<TrainingEnrollmentDto>>(
    TRAINING_API_ROUTES.adminEnrollmentStatus(id),
    { status },
  );
  return unwrapTrainingData(res.data);
};
