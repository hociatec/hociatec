import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import type { ApiMutationResult, ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
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
    return {
      data,
      message: extractApiErrorMessage(res.data, res.data.message),
    };
  }, 'Inscription impossible.');
};

export const fetchMyTrainingEnrollments = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<TrainingEnrollmentDto>> => {
  return trainingRequest(async () => {
    const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[]; meta: PaginationMeta }>>(
      TRAINING_API_ROUTES.myEnrollments,
      { params: { page, perPage } },
    );
    return unwrapTrainingData(res.data);
  }, 'Impossible de charger vos inscriptions.');
};
