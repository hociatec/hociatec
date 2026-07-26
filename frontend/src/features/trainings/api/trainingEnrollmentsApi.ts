import { httpClient, getHttpErrorMessage } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { TrainingEnrollmentDto } from './trainingTypes';

const unwrapData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') throw new Error(response.message);
  return response.data;
};

export const enrollTrainingSession = async (
  sessionId: number,
  startsAt: string,
): Promise<TrainingEnrollmentDto> => {
  try {
    const res = await httpClient.post<ApiResponse<TrainingEnrollmentDto>>(
      '/api/trainings/enrollments',
      { sessionId, startsAt },
    );
    return unwrapData(res.data);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Inscription impossible.'));
  }
};

export const fetchMyTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    '/api/trainings/enrollments/me',
  );
  return unwrapData(res.data).items;
};
