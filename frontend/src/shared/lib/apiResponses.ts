import { isApiOk, type ApiResponse } from '@/shared/types/api';

export const extractApiErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error'
    ? response.message && response.message.trim() !== ''
      ? response.message
      : fallback
    : fallback;

export interface ApiErrorWithDetails extends Error {
  details?: string[];
}

export const createApiError = (response: ApiResponse<unknown>, fallback: string): ApiErrorWithDetails => {
  const error = new Error(extractApiErrorMessage(response, fallback)) as ApiErrorWithDetails;
  const details = response.status === 'error'
    ? response.error?.details ?? response.details
    : undefined;
  if (Array.isArray(details)) {
    error.details = details.map((detail) => String(detail));
  }
  return error;
};

export const unwrapApiData = <T>(response: ApiResponse<T>, fallback: string): T => {
  if (isApiOk(response)) {
    return response.data;
  }

  throw createApiError(response, fallback);
};
