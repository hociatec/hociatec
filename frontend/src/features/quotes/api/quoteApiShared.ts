import { isAxiosError } from 'axios';

import type { ApiResponse } from '@/shared/types/api';

export const unwrapQuoteApiData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') throw new Error(response.message);
  return response.data;
};

export const unwrapQuoteApiResult = <T>(response: ApiResponse<T>) => ({
  data: unwrapQuoteApiData(response),
  message: response.status === 'error' ? undefined : response.message,
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
