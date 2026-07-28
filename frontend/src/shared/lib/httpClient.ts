import axios, { AxiosHeaders, isAxiosError } from 'axios';

import { API_BASE_URL } from '../config/appConfig';
import { createApiResponseError } from './httpErrors';
import { createAuthSessionRefresher, isAuthRefreshRequest, type RetriableRequestConfig } from './http/authRefresh';
import { CSRF_HEADER_NAME, fetchCsrfToken, shouldAttachCsrfToken } from './http/csrf';
import { getPersistedCartToken } from './http/tokens';

export {
  ApiResponseError,
  createApiResponseError,
  getHttpErrorMessage,
  getHttpErrorMessageAsync,
} from './httpErrors';
export {
  clearAuthToken,
  clearCartToken,
  getPersistedCartToken,
  persistCartToken,
  purgeLegacyAuthLocalStorage,
} from './http/tokens';
export { shouldAttachCsrfToken } from './http/csrf';

export const httpClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

const refreshAuthSession = createAuthSessionRefresher(httpClient);

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
    if (!isAxiosError(error) || error.response?.status !== 401 || !error.config) {
      throw error;
    }

    const originalRequest = error.config as RetriableRequestConfig;
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
