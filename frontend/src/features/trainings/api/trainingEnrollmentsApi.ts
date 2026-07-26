import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';
import type { TrainingEnrollmentDto } from './trainingTypes';
import { TRAINING_API_ROUTES, trainingRequest, unwrapTrainingData } from './trainingApiShared';

export const enrollTrainingSession = async (
  sessionId: number,
  startsAt: string,
): Promise<TrainingEnrollmentDto> => {
  return trainingRequest(async () => {
    const res = await httpClient.post<ApiResponse<TrainingEnrollmentDto>>(
      TRAINING_API_ROUTES.enrollments,
      { sessionId, startsAt },
    );
    return unwrapTrainingData(res.data);
  }, 'Inscription impossible.');
};

export const fetchMyTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
      TRAINING_API_ROUTES.myEnrollments,
    );
    return unwrapTrainingData(res.data).items;
  }, 'Impossible de charger vos inscriptions.');
};
