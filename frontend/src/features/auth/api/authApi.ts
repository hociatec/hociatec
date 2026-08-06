import { clearCsrfToken, httpClient } from '../../../shared/lib/httpClient';
import axios from 'axios';
import type { ApiResponse } from '../../../shared/types/api';
import type { AuthUser } from '../../../shared/types/auth';
import { createApiError } from '@/shared/lib/apiResponses';
import { parseAuthUser } from '../lib/authValidation';

export interface RegisterPayload {
  email: string;
  password: string;
  confirmPassword: string;
  firstName: string;
  lastName: string;
  birthDate: string;
  phoneNumber: string;
  gender: string;
  isBetaTester?: boolean;
  betaConsent?: boolean;
  availability?: string[];
  motivation?: string;
  testingExperience?: string[];
  bugDescriptionAbility?: string[];
  technicalKnowledge?: string[];
  accessibilityNeed?: string;
  assistiveTools?: string[];
  devices?: string[];
  browsers?: string[];
  testingTypes?: string[];
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

interface AuthMeAuthenticated extends AuthUser {
  authenticated: true;
}

interface AuthMeUnauthenticated {
  authenticated: false;
}

type AuthMeResponse = AuthMeAuthenticated | AuthMeUnauthenticated;

export interface PasswordResetPayload {
  password: string;
  confirmPassword: string;
}

export interface AuthOperationResult<T> {
  data: T;
  message?: string | null | undefined;
}

const unwrapResponse = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    throw createApiError(response, response.message);
  }

  return response.data;
};

const rethrowApiError = (error: unknown): never => {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiResponse<unknown> | undefined;
    if (data && data.status === 'error') {
      throw createApiError(data, data.message);
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
    clearCsrfToken();
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
  } finally {
    clearCsrfToken();
  }
};

export const fetchCurrentUser = async (): Promise<AuthUser | null> => {
  const { data } = await httpClient.get<ApiResponse<AuthMeResponse>>('/api/auth/me');
  const payload = unwrapResponse(data);

  if (payload.authenticated === false) {
    return null;
  }

  const { authenticated: _authenticated, ...user } = payload;

  return parseAuthUser(user);
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

  return parseAuthUser(unwrapResponse(data));
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
