import axios, { AxiosHeaders } from 'axios';

import { API_BASE_URL } from '@/shared/config/appConfig';
import { getRequestPath } from './requestPaths';

export const CSRF_HEADER_NAME = 'X-CSRF-Token';
const CSRF_TOKEN_PATH = '/api/csrf-token';
const UNSAFE_METHODS = new Set(['post', 'put', 'patch', 'delete']);
const CSRF_EXCLUDED_PREFIXES = [
  '/api/auth/login',
  '/api/auth/logout',
  '/api/auth/refresh',
  '/api/auth/register',
  '/api/auth/verify',
  '/api/auth/password-reset',
  '/api/stripe/webhook',
];

let csrfToken: string | null = null;
let csrfTokenRequest: Promise<string> | null = null;

const isUnsafeMethod = (method?: string) => UNSAFE_METHODS.has((method ?? 'get').toLowerCase());

const isCsrfTokenRequest = (url?: string) => {
  if (!url) return false;
  return url === CSRF_TOKEN_PATH || url.endsWith(CSRF_TOKEN_PATH);
};

const isCsrfExcludedRequest = (url?: string) => {
  if (!url) return false;
  const path = getRequestPath(url);

  return CSRF_EXCLUDED_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
};

export const fetchCsrfToken = async () => {
  if (csrfToken) return csrfToken;

  csrfTokenRequest ??= axios
    .get<{ data?: { token?: unknown } }>(CSRF_TOKEN_PATH, {
      baseURL: API_BASE_URL,
      withCredentials: true,
      headers: { Accept: 'application/json' },
    })
    .then((response) => {
      const token = response.data?.data?.token;
      if (typeof token !== 'string' || token.trim() === '') {
        throw new Error('Jeton CSRF manquant.');
      }

      csrfToken = token;
      return token;
    })
    .finally(() => {
      csrfTokenRequest = null;
    });

  return csrfTokenRequest;
};

export const shouldAttachCsrfToken = (
  method?: string,
  url?: string,
  headers = new AxiosHeaders(),
) =>
  isUnsafeMethod(method) &&
  !isCsrfTokenRequest(url) &&
  !isCsrfExcludedRequest(url) &&
  !headers.has(CSRF_HEADER_NAME);
