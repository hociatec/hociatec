import type { ApiResponse } from '@/shared/types/api';

export const extractErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error' ? response.message : fallback;
