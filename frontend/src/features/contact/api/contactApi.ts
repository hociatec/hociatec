import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import type { ApiMutationResult, ApiResponse } from '@/shared/types/api';

export interface ContactPayload {
  name: string;
  email: string;
  subject: string;
  message: string;
}

export const sendContactMessage = async (payload: ContactPayload): Promise<ApiMutationResult<null>> => {
  const { data } = await httpClient.post<ApiResponse<null>>(
    '/api/public/contact',
    payload,
  );

  const responseData = unwrapApiData(data, 'Impossible d’envoyer votre message.');
  return { data: responseData, message: data.message };
};
