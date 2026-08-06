import { httpClient } from '@/shared/lib/httpClient';
import { createApiError, extractApiErrorMessage } from '@/shared/lib/apiResponses';
import type { ApiMutationResult, ApiResponse } from '@/shared/types/api';

export interface ContactPayload {
  name: string;
  email: string;
  subject: string;
  message: string;
}

const unwrapResponse = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    throw createApiError(response, response.message);
  }
  return response.data;
};

export const sendContactMessage = async (payload: ContactPayload): Promise<ApiMutationResult<null>> => {
  const { data } = await httpClient.post<ApiResponse<null>>(
    '/api/public/contact',
    payload,
  );

  unwrapResponse(data);
  return { data: null, message: extractApiErrorMessage(data, '') };
};
