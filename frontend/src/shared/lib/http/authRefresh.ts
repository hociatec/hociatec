import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

import { publishAuthSessionEvent } from '../authSessionEvents';
import { clearCsrfToken } from './csrf';
import { getRequestPath } from './requestPaths';

export type RetriableRequestConfig = InternalAxiosRequestConfig & {
  _retryAfterAuthRefresh?: boolean;
  _retryAfterCsrfRefresh?: boolean;
};

export const isAuthRefreshRequest = (url?: string) => getRequestPath(url) === '/api/auth/refresh';

export const createAuthSessionRefresher = (client: AxiosInstance) => {
  let authRefreshRequest: Promise<void> | null = null;

  return async () => {
    authRefreshRequest ??= client
      .post('/api/auth/refresh', undefined, {
        headers: { Accept: 'application/json' },
      })
      .then(() => {
        clearCsrfToken();
      })
      .catch((error) => {
        publishAuthSessionEvent('session_revoked');

        throw error;
      })
      .finally(() => {
        authRefreshRequest = null;
      });

    return authRefreshRequest;
  };
};
