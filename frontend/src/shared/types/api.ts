export interface ApiSuccess<T> {
  status: 'success';
  data: T;
}

export interface ApiError {
  status: 'error';
  message: string;
  details?: string[];
}

export interface ApiCreated<T> {
  status: 'created';
  data: T;
}

export type ApiResponse<T> = ApiSuccess<T> | ApiCreated<T> | ApiError;

export const isApiOk = <T>(response: ApiResponse<T>): response is ApiSuccess<T> | ApiCreated<T> =>
  response.status === 'success' || response.status === 'created';
