import { httpClient } from '../../../shared/lib/httpClient';
import axios from 'axios';
import type { ApiResponse } from '../../../shared/types/api';
import type { AuthTokens, AuthUser } from '../../../shared/types/auth';

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

const unwrapResponse = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    const error = new Error(response.message);
    (error as Error & { details?: string[] }).details = response.details;
    throw error;
  }

  return response.data;
};

export const registerUser = async (payload: RegisterPayload) => {
  try {
    const { data } = await httpClient.post<ApiResponse<AuthUser>>(
      '/api/auth/register',
      payload,
    );
    return unwrapResponse(data);
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as ApiResponse<unknown> | undefined;
      if (data && data.status === 'error') {
        const err = new Error(data.message) as Error & { details?: string[] };
        err.details = (data as { details?: string[] }).details ?? [];
        throw err;
      }
    }
    throw error;
  }
};

export const loginUser = async (payload: LoginPayload) => {
  const { data } = await httpClient.post<AuthTokens>('/api/auth/login', payload);

  return data;
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
}

export const updateProfile = async (payload: UpdateProfilePayload) => {
  const { data } = await httpClient.put<ApiResponse<AuthUser>>(
    '/api/auth/profile',
    payload,
  );

  return unwrapResponse(data);
};

export const deleteAccount = async () => {
  const { data } = await httpClient.delete<ApiResponse<{ message: string }>>(
    '/api/auth/profile',
  );

  return unwrapResponse(data);
};

export const verifyAccount = async (token: string) => {
  const { data } = await httpClient.get<ApiResponse<{ message: string }>>(
    `/api/auth/verify/${token}`,
  );

  return unwrapResponse(data);
};
