import axios, { AxiosHeaders, isAxiosError } from 'axios';

import { API_BASE_URL } from '../config/appConfig';
import { createApiResponseError } from './httpErrors';
import { createAuthSessionRefresher, isAuthRefreshRequest, type RetriableRequestConfig } from './http/authRefresh';
import {
  clearCsrfToken,
  CSRF_HEADER_NAME,
  fetchCsrfToken,
  isCsrfFailureResponse,
  shouldAttachCsrfToken,
} from './http/csrf';
import { getPersistedCartToken } from './http/tokens';

export {
  ApiResponseError,
  createApiResponseError,
  getHttpErrorMessage,
  getHttpErrorMessageAsync,
  normalizeHttpError,
} from './httpErrors';
export {
  clearAuthToken,
  clearCartToken,
  getPersistedCartToken,
  persistCartToken,
  purgeLegacyAuthLocalStorage,
} from './http/tokens';
export { clearCsrfToken, isCsrfFailureResponse, shouldAttachCsrfToken } from './http/csrf';

export const httpClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  timeout: 15_000,
});

const refreshAuthSession = createAuthSessionRefresher(httpClient);
export const IDEMPOTENCY_HEADER_NAME = 'Idempotency-Key';

export const shouldAttachIdempotencyKey = (method?: string) => {
  const normalizedMethod = method?.toLowerCase() ?? 'get';

  return ['post', 'put', 'patch', 'delete'].includes(normalizedMethod);
};

const createIdempotencyKey = () => {
  if (typeof globalThis.crypto?.randomUUID === 'function') {
    return globalThis.crypto.randomUUID();
  }

  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
};

httpClient.interceptors.request.use(async (config) => {
  const headers =
    config.headers instanceof AxiosHeaders ? config.headers : new AxiosHeaders(config.headers);

  const cartToken = getPersistedCartToken();
  if (cartToken && !headers.has('X-Cart-Token')) {
    headers.set('X-Cart-Token', cartToken);
  }

  if (shouldAttachCsrfToken(config.method, config.url, headers)) {
    headers.set(CSRF_HEADER_NAME, await fetchCsrfToken());
  }

  if (shouldAttachIdempotencyKey(config.method) && !headers.has(IDEMPOTENCY_HEADER_NAME)) {
    headers.set(IDEMPOTENCY_HEADER_NAME, createIdempotencyKey());
  }

  config.headers = headers;

  return config;
});

httpClient.interceptors.response.use(
  (response) => {
    const apiError = createApiResponseError(response.data);
    if (apiError) throw apiError;

    return response;
  },
  async (error: unknown) => {
    if (!isAxiosError(error) || !error.config) {
      throw error;
    }

    const originalRequest = error.config as RetriableRequestConfig;
    if (
      isCsrfFailureResponse(error.response?.status, error.response?.data) &&
      !originalRequest._retryAfterCsrfRefresh
    ) {
      originalRequest._retryAfterCsrfRefresh = true;
      clearCsrfToken();

      const headers =
        originalRequest.headers instanceof AxiosHeaders
          ? originalRequest.headers
          : new AxiosHeaders(originalRequest.headers);
      headers.delete(CSRF_HEADER_NAME);
      originalRequest.headers = headers;

      return httpClient(originalRequest);
    }

    if (error.response?.status !== 401) {
      throw error;
    }

    if (originalRequest._retryAfterAuthRefresh || isAuthRefreshRequest(originalRequest.url)) {
      throw error;
    }

    originalRequest._retryAfterAuthRefresh = true;

    try {
      await refreshAuthSession();
    } catch {
      throw error;
    }

    return httpClient(originalRequest);
  },
);
