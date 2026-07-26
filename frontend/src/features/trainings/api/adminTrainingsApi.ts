import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
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
import { TRAINING_API_ROUTES, unwrapTrainingData } from './trainingApiShared';

export const fetchAdminTrainings = async (): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>(TRAINING_API_ROUTES.adminTrainings);
  return unwrapTrainingData(res.data).items;
};

export const fetchAdminTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    TRAINING_API_ROUTES.adminCategories,
  );
  return unwrapTrainingData(res.data).items;
};

export const createAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
): Promise<TrainingCategoryDto> => {
  const res = await httpClient.post<ApiResponse<TrainingCategoryDto>>(
    TRAINING_API_ROUTES.adminCategories,
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const updateAdminTrainingCategory = async (
  id: number,
  payload: TrainingCategoryInput,
): Promise<TrainingCategoryDto> => {
  const res = await httpClient.post<ApiResponse<TrainingCategoryDto>>(
    TRAINING_API_ROUTES.adminCategory(id),
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const saveAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
  id?: number,
): Promise<TrainingCategoryDto> =>
  id === undefined
    ? createAdminTrainingCategory(payload)
    : updateAdminTrainingCategory(id, payload);

export const deleteAdminTrainingCategory = async (id: number): Promise<void> => {
  await httpClient.delete(TRAINING_API_ROUTES.adminCategory(id));
};

export const fetchAdminTraining = async (id: number): Promise<TrainingDto> => {
  const res = await httpClient.get<ApiResponse<TrainingDto>>(TRAINING_API_ROUTES.adminTraining(id));
  return unwrapTrainingData(res.data);
};

export const createAdminTraining = async (payload: TrainingInput): Promise<TrainingDto> => {
  const res = await httpClient.post<ApiResponse<TrainingDto>>(
    TRAINING_API_ROUTES.adminTrainings,
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const updateAdminTraining = async (
  id: number,
  payload: TrainingInput,
): Promise<TrainingDto> => {
  const res = await httpClient.post<ApiResponse<TrainingDto>>(
    TRAINING_API_ROUTES.adminTraining(id),
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const saveAdminTraining = async (
  payload: TrainingInput,
  id?: number,
): Promise<TrainingDto> =>
  id === undefined ? createAdminTraining(payload) : updateAdminTraining(id, payload);

export const deleteAdminTraining = async (id: number): Promise<void> => {
  await httpClient.delete(TRAINING_API_ROUTES.adminTraining(id));
};

export const fetchAdminTrainingSessions = async (): Promise<TrainingSessionDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingSessionDto[] }>>(
    TRAINING_API_ROUTES.adminSessions,
  );
  return unwrapTrainingData(res.data).items;
};

export const createAdminTrainingSession = async (
  payload: TrainingSessionInput,
): Promise<TrainingSessionDto> => {
  const res = await httpClient.post<ApiResponse<TrainingSessionDto>>(
    TRAINING_API_ROUTES.adminSessions,
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const updateAdminTrainingSession = async (
  id: number,
  payload: TrainingSessionInput,
): Promise<TrainingSessionDto> => {
  const res = await httpClient.post<ApiResponse<TrainingSessionDto>>(
    TRAINING_API_ROUTES.adminSession(id),
    payload,
  );
  return unwrapTrainingData(res.data);
};

export const saveAdminTrainingSession = async (
  payload: TrainingSessionInput,
  id?: number,
): Promise<TrainingSessionDto> =>
  id === undefined
    ? createAdminTrainingSession(payload)
    : updateAdminTrainingSession(id, payload);

export const deleteAdminTrainingSession = async (id: number): Promise<void> => {
  await httpClient.delete(TRAINING_API_ROUTES.adminSession(id));
};

export const fetchAdminTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    TRAINING_API_ROUTES.adminEnrollments,
  );
  return unwrapTrainingData(res.data).items;
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
