import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

import { getRequestPath } from './requestPaths';

export type RetriableRequestConfig = InternalAxiosRequestConfig & {
  _retryAfterAuthRefresh?: boolean;
};

export const isAuthRefreshRequest = (url?: string) => getRequestPath(url) === '/api/auth/refresh';

export const createAuthSessionRefresher = (client: AxiosInstance) => {
  let authRefreshRequest: Promise<void> | null = null;

  return async () => {
    authRefreshRequest ??= client
      .post('/api/auth/refresh', undefined, {
        headers: { Accept: 'application/json' },
      })
      .then(() => undefined)
      .finally(() => {
        authRefreshRequest = null;
      });

    return authRefreshRequest;
  };
};
