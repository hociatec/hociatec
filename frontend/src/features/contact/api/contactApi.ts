import { httpClient } from '@/shared/lib/httpClient';
import type { ApiMutationResult, ApiResponse } from '@/shared/types/api';

export interface ContactPayload {
  name: string;
  email: string;
  subject: string;
  message: string;
}

const unwrapResponse = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    const error = new Error(response.message);
    (error as Error & { details?: string[] }).details = response.details;
    throw error;
  }
  return response.data;
};

export const sendContactMessage = async (payload: ContactPayload): Promise<ApiMutationResult<null>> => {
  const { data } = await httpClient.post<ApiResponse<null>>(
    '/api/public/contact',
    payload,
  );

  unwrapResponse(data);
  return { data: null, message: data.status === 'error' ? undefined : data.message };
};
