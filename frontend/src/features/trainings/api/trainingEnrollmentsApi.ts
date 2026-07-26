import { httpClient } from '@/shared/lib/httpClient';
import type { ApiMutationResult, ApiResponse } from '@/shared/types/api';
import type { TrainingEnrollmentDto } from './trainingTypes';
import { TRAINING_API_ROUTES, trainingRequest, unwrapTrainingData } from './trainingApiShared';

export const enrollTrainingSession = async (
  sessionId: number,
  startsAt: string,
): Promise<ApiMutationResult<TrainingEnrollmentDto & { checkoutUrl?: string | null }>> => {
  return trainingRequest(async () => {
    const res = await httpClient.post<ApiResponse<TrainingEnrollmentDto>>(
      TRAINING_API_ROUTES.enrollments,
      { sessionId, startsAt },
    );
    const data = unwrapTrainingData(res.data);
    return { data, message: res.data.status === 'error' ? undefined : res.data.message };
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
