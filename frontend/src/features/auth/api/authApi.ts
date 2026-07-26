import { httpClient } from '../../../shared/lib/httpClient';
import axios from 'axios';
import type { ApiResponse } from '../../../shared/types/api';
import type { AuthUser } from '../../../shared/types/auth';

export interface RegisterPayload {
  email: string;
  password: string;
  confirmPassword: string;
  firstName: string;
  lastName: string;
  birthDate: string;
  phoneNumber: string;
  gender: string;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginFormPayload extends LoginPayload {
  rememberMe?: boolean;
}

export interface AuthSession {
  authenticated: boolean;
  refreshTokenExpiresAt?: string;
}

export interface PasswordResetPayload {
  password: string;
  confirmPassword: string;
}

export interface AuthOperationResult<T> {
  data: T;
  message?: string;
}

const unwrapResponse = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    const error = new Error(response.message);
    (error as Error & { details?: string[] }).details = response.details;
    throw error;
  }

  return response.data;
};

const rethrowApiError = (error: unknown): never => {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiResponse<unknown> | undefined;
    if (data && data.status === 'error') {
      const err = new Error(data.message) as Error & { details?: string[] };
      err.details = (data as { details?: string[] }).details ?? [];
      throw err;
    }
  }

  throw error;
};

export const registerUser = async (payload: RegisterPayload): Promise<AuthOperationResult<AuthUser>> => {
  try {
    const { data } = await httpClient.post<ApiResponse<AuthUser>>('/api/auth/register', payload);
    return { data: unwrapResponse(data), message: data.message };
  } catch (error) {
    return rethrowApiError(error);
  }
};

export const loginUser = async (payload: LoginPayload): Promise<AuthOperationResult<AuthSession>> => {
  try {
    const { data } = await httpClient.post<ApiResponse<AuthSession>>('/api/auth/login', payload);
    return { data: unwrapResponse(data), message: data.message };
  } catch (error) {
    return rethrowApiError(error);
  }
};

export const refreshUserSession = async (): Promise<AuthOperationResult<AuthSession>> => {
  try {
    const { data } = await httpClient.post<ApiResponse<AuthSession>>('/api/auth/refresh');
    return { data: unwrapResponse(data), message: data.message };
  } catch (error) {
    return rethrowApiError(error);
  }
};

export const logoutUser = async () => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ message: string }>>('/api/auth/logout');
    return unwrapResponse(data);
  } catch (error) {
    return rethrowApiError(error);
  }
};

export const fetchCurrentUser = async () => {
  const { data } = await httpClient.get<ApiResponse<AuthUser>>('/api/auth/me');

  return unwrapResponse(data);
};

export interface UpdateProfilePayload {
  firstName: string;
  lastName: string;
  email: string;
  birthDate: string;
  phoneNumber: string;
  gender: string;
  password?: string;
  currentPassword?: string;
}

export const updateProfile = async (payload: UpdateProfilePayload) => {
  const { data } = await httpClient.put<ApiResponse<AuthUser>>('/api/auth/profile', payload);

  return unwrapResponse(data);
};

export const deleteAccount = async () => {
  const { data } = await httpClient.delete<ApiResponse<{ message: string }>>('/api/auth/profile');

  return unwrapResponse(data);
};

export const verifyAccount = async (token: string) => {
  const { data } = await httpClient.get<ApiResponse<{ message: string }>>(
    `/api/auth/verify/${token}`,
  );

  return unwrapResponse(data);
};

export const requestPasswordReset = async (email: string): Promise<{ message: string }> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ message: string }>>(
      '/api/auth/password-reset/request',
      { email },
    );

    return unwrapResponse(data);
  } catch (error) {
    return rethrowApiError(error);
  }
};

export const resetPassword = async (
  token: string,
  payload: PasswordResetPayload,
): Promise<{ message: string }> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ message: string }>>(
      `/api/auth/password-reset/reset/${token}`,
      payload,
    );

    return unwrapResponse(data);
  } catch (error) {
    return rethrowApiError(error);
  }
};
