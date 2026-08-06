import axios, { AxiosHeaders, isAxiosError, type AxiosRequestConfig } from 'axios';

import { API_BASE_URL, BUILD_INFO } from '../config/appConfig';
import { normalizeSearchText } from '@/shared/lib/searchText';
import { createApiResponseError } from './httpErrors';
import { createAuthSessionRefresher, isAuthRefreshRequest, type RetriableRequestConfig } from './http/authRefresh';
import {
  clearCsrfToken,
  CSRF_HEADER_NAME,
  fetchCsrfToken,
  isCsrfFailureResponse,
  shouldAttachCsrfToken,
} from './http/csrf';
import { FRONTEND_REQUEST_ID_HEADER_NAME, IDEMPOTENCY_HEADER_NAME } from './http/headers';
import { getPersistedCartToken } from './http/tokens';
import { reportError } from './observability';
import { createRandomId } from './random';

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

export const requestSignalConfig = (signal?: AbortSignal): AxiosRequestConfig =>
  signal ? { signal } : {};

const refreshAuthSession = createAuthSessionRefresher(httpClient);
const FRONTEND_VERSION_HEADER_NAME = 'X-Frontend-Version';
const FRONTEND_COMMIT_HEADER_NAME = 'X-Frontend-Commit';
const FRONTEND_ENV_HEADER_NAME = 'X-Frontend-Env';

export const shouldAttachIdempotencyKey = (method?: string) => {
  const normalizedMethod = method ? normalizeSearchText(method).trim() : 'get';

  return ['post', 'put', 'patch', 'delete'].includes(normalizedMethod);
};

const createIdempotencyKey = () => {
  if (typeof globalThis.crypto?.randomUUID === 'function') {
    return globalThis.crypto.randomUUID();
  }

  return createRandomId('idempotency');
};

httpClient.interceptors.request.use(async (config) => {
  const headers =
    config.headers instanceof AxiosHeaders ? config.headers : new AxiosHeaders(config.headers);

  if (!headers.has(FRONTEND_VERSION_HEADER_NAME)) {
    headers.set(FRONTEND_VERSION_HEADER_NAME, BUILD_INFO.frontendVersion);
  }

  if (!headers.has(FRONTEND_COMMIT_HEADER_NAME)) {
    headers.set(FRONTEND_COMMIT_HEADER_NAME, BUILD_INFO.commitSha);
  }

  if (!headers.has(FRONTEND_ENV_HEADER_NAME)) {
    headers.set(FRONTEND_ENV_HEADER_NAME, BUILD_INFO.environment);
  }

  if (!headers.has(FRONTEND_REQUEST_ID_HEADER_NAME)) {
    headers.set(FRONTEND_REQUEST_ID_HEADER_NAME, createRandomId('front_req'));
  }

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
      reportError(error, { category: 'network', message: 'Unhandled HTTP client error.' });
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
      if (!error.response || error.response.status >= 500) {
        const requestHeaders =
          originalRequest.headers instanceof AxiosHeaders
            ? originalRequest.headers
            : new AxiosHeaders(originalRequest.headers);

        reportError(error, {
          category: 'network',
          frontendRequestId: requestHeaders.get(FRONTEND_REQUEST_ID_HEADER_NAME),
          message: 'HTTP request failed.',
          method: originalRequest.method,
          status: error.response?.status,
          url: originalRequest.url,
        });
      }

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
