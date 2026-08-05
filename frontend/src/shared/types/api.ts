export interface ApiSuccess<T> {
  status: 'success';
  data: T;
  meta: Record<string, unknown>;
  message: string | null;
}

export interface ApiError {
  status: 'error';
  error?: {
    code?: string;
    message: string;
    fields?: Record<string, string[]>;
    details?: unknown;
    requestId?: string | null;
  };
  code?: string;
  message: string;
  details?: unknown;
}

export interface ApiCreated<T> {
  status: 'created';
  data: T;
  meta?: Record<string, unknown>;
  message?: string | null;
}

export type ApiResponse<T> = ApiSuccess<T> | ApiCreated<T> | ApiError;

export interface ApiMutationResult<T> {
  data: T;
  message?: string | null;
}

export const isApiOk = <T>(response: ApiResponse<T>): response is ApiSuccess<T> | ApiCreated<T> =>
  response.status === 'success' || response.status === 'created';
