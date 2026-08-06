import { isAxiosError } from 'axios';

import type { ApiResponse } from '@/shared/types/api';
import { unwrapApiData } from '@/shared/lib/responseHelpers';

export const unwrapQuoteApiData = <T>(response: ApiResponse<T>): T => {
  return unwrapApiData(response, response.message && response.message.trim() !== '' ? response.message : 'Réponse API invalide.');
};

export const unwrapQuoteApiResult = <T>(response: ApiResponse<T>) => ({
  data: unwrapQuoteApiData(response),
  message: response.message,
});

export const extractQuoteApiError = (error: unknown, fallback: string) => {
  const responseData = isAxiosError(error)
    ? (error.response?.data as
        { message?: unknown; error?: unknown; data?: { message?: unknown } } | undefined)
    : undefined;
  const apiMessage = responseData?.message ?? responseData?.error ?? responseData?.data?.message;

  return typeof apiMessage === 'string' && apiMessage.trim() !== ''
    ? apiMessage
    : error instanceof Error && error.message
      ? error.message
      : fallback;
};
