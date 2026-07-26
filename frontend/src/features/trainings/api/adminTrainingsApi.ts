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

const unwrapData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') throw new Error(response.message);
  return response.data;
};

export const fetchAdminTrainings = async (): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>('/api/admin/trainings');
  return unwrapData(res.data).items;
};

export const fetchAdminTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    '/api/admin/training-categories',
  );
  return unwrapData(res.data).items;
};

export const saveAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
  id?: number,
): Promise<TrainingCategoryDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingCategoryDto>>(
        `/api/admin/training-categories/${id}`,
        payload,
      )
    : await httpClient.post<ApiResponse<TrainingCategoryDto>>(
        '/api/admin/training-categories',
        payload,
      );
  return unwrapData(res.data);
};

export const deleteAdminTrainingCategory = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/training-categories/${id}`);
};

export const fetchAdminTraining = async (id: number): Promise<TrainingDto> => {
  const res = await httpClient.get<ApiResponse<TrainingDto>>(`/api/admin/trainings/${id}`);
  return unwrapData(res.data);
};

export const saveAdminTraining = async (
  payload: TrainingInput,
  id?: number,
): Promise<TrainingDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingDto>>(`/api/admin/trainings/${id}`, payload)
    : await httpClient.post<ApiResponse<TrainingDto>>('/api/admin/trainings', payload);
  return unwrapData(res.data);
};

export const deleteAdminTraining = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/trainings/${id}`);
};

export const fetchAdminTrainingSessions = async (): Promise<TrainingSessionDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingSessionDto[] }>>(
    '/api/admin/training-sessions',
  );
  return unwrapData(res.data).items;
};

export const saveAdminTrainingSession = async (
  payload: TrainingSessionInput,
  id?: number,
): Promise<TrainingSessionDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingSessionDto>>(
        `/api/admin/training-sessions/${id}`,
        payload,
      )
    : await httpClient.post<ApiResponse<TrainingSessionDto>>(
        '/api/admin/training-sessions',
        payload,
      );
  return unwrapData(res.data);
};

export const deleteAdminTrainingSession = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/training-sessions/${id}`);
};

export const fetchAdminTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    '/api/admin/training-enrollments',
  );
  return unwrapData(res.data).items;
};

export const updateAdminTrainingEnrollmentStatus = async (
  id: number,
  status: TrainingEnrollmentStatus,
): Promise<TrainingEnrollmentDto> => {
  const res = await httpClient.patch<ApiResponse<TrainingEnrollmentDto>>(
    `/api/admin/training-enrollments/${id}/status`,
    { status },
  );
  return unwrapData(res.data);
};
