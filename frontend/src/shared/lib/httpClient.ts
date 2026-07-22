import axios, { AxiosHeaders, isAxiosError, type InternalAxiosRequestConfig } from 'axios';

import { API_BASE_URL } from '../config/appConfig';

const CART_TOKEN_KEY = 'hociatec.cart.token';
const LEGACY_AUTH_TOKEN_KEY = 'hociatec.auth.token';
const LEGACY_AUTH_REFRESH_TOKEN_KEY = 'hociatec.auth.refresh.token';
const LEGACY_AUTH_SESSION_TOKEN_KEY = 'hociatec.auth.session.token';
const LEGACY_AUTH_SESSION_REFRESH_TOKEN_KEY = 'hociatec.auth.session.refresh.token';
const CSRF_HEADER_NAME = 'X-CSRF-Token';
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

type RetriableRequestConfig = InternalAxiosRequestConfig & {
  _retryAfterAuthRefresh?: boolean;
};

const hasLocalStorage = typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
const hasSessionStorage = typeof window !== 'undefined' && typeof window.sessionStorage !== 'undefined';

const readLocalStorage = (key: string) => {
  if (!hasLocalStorage) return null;
  try {
    return window.localStorage.getItem(key);
  } catch {
    return null;
  }
};

const writeLocalStorage = (key: string, value: string) => {
  if (!hasLocalStorage) return;
  try {
    window.localStorage.setItem(key, value);
  } catch {
    /* noop */
  }
};

const removeLocalStorage = (key: string) => {
  if (!hasLocalStorage) return;
  try {
    window.localStorage.removeItem(key);
  } catch {
    /* noop */
  }
};

const removeSessionStorage = (key: string) => {
  if (!hasSessionStorage) return;
  try {
    window.sessionStorage.removeItem(key);
  } catch {
    /* noop */
  }
};

export const httpClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

let csrfToken: string | null = null;
let csrfTokenRequest: Promise<string> | null = null;

const isUnsafeMethod = (method?: string) => UNSAFE_METHODS.has((method ?? 'get').toLowerCase());

const isCsrfTokenRequest = (url?: string) => {
  if (!url) return false;
  return url === CSRF_TOKEN_PATH || url.endsWith(CSRF_TOKEN_PATH);
};

const getRequestPath = (url?: string) => {
  if (!url) return '';
  const baseUrl = API_BASE_URL && !API_BASE_URL.startsWith('/')
    ? API_BASE_URL
    : `http://localhost${API_BASE_URL || ''}`;

  return new URL(url, baseUrl).pathname;
};

const isAuthRefreshRequest = (url?: string) => getRequestPath(url) === '/api/auth/refresh';

const isCsrfExcludedRequest = (url?: string) => {
  if (!url) return false;
  const path = getRequestPath(url);

  return CSRF_EXCLUDED_PREFIXES.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
};

const fetchCsrfToken = async () => {
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
  isUnsafeMethod(method)
  && !isCsrfTokenRequest(url)
  && !isCsrfExcludedRequest(url)
  && !headers.has(CSRF_HEADER_NAME);

httpClient.interceptors.request.use(async (config) => {
  const headers =
    config.headers instanceof AxiosHeaders ? config.headers : new AxiosHeaders(config.headers);

  const cartToken = readLocalStorage(CART_TOKEN_KEY);
  if (cartToken && !headers.has('X-Cart-Token')) {
    headers.set('X-Cart-Token', cartToken);
  }

  if (shouldAttachCsrfToken(config.method, config.url, headers)) {
    headers.set(CSRF_HEADER_NAME, await fetchCsrfToken());
  }

  config.headers = headers;

  return config;
});

let authRefreshRequest: Promise<void> | null = null;

const refreshAuthSession = async () => {
  authRefreshRequest ??= httpClient
    .post('/api/auth/refresh', undefined, {
      headers: { Accept: 'application/json' },
    })
    .then(() => undefined)
    .finally(() => {
      authRefreshRequest = null;
    });

  return authRefreshRequest;
};

httpClient.interceptors.response.use(
  (response) => response,
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

export const purgeLegacyAuthLocalStorage = () => {
  removeLocalStorage(LEGACY_AUTH_TOKEN_KEY);
  removeLocalStorage(LEGACY_AUTH_REFRESH_TOKEN_KEY);
  removeSessionStorage(LEGACY_AUTH_SESSION_TOKEN_KEY);
  removeSessionStorage(LEGACY_AUTH_SESSION_REFRESH_TOKEN_KEY);
};

export const clearAuthToken = () => {
  purgeLegacyAuthLocalStorage();
};

export const persistCartToken = (token: string) => writeLocalStorage(CART_TOKEN_KEY, token);

export const clearCartToken = () => removeLocalStorage(CART_TOKEN_KEY);

export const getPersistedCartToken = () => readLocalStorage(CART_TOKEN_KEY);

export const getHttpErrorMessage = (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  if (!isAxiosError(error)) {
    return error instanceof Error && error.message ? error.message : fallback;
  }

  const responseData = error.response?.data as { message?: unknown } | undefined;
  const apiMessage = typeof responseData?.message === 'string' ? responseData.message.trim() : '';

  if (apiMessage) {
    return apiMessage;
  }

  if (!error.response) {
    return 'Le service est momentanément indisponible. Vérifiez que le serveur API est démarré, puis réessayez.';
  }

  if (error.response.status >= 500) {
    return 'Le service rencontre un problème temporaire. Veuillez réessayer dans quelques instants.';
  }

  if (error.response.status === 404) {
    return 'La ressource demandée est introuvable.';
  }

  if (error.response.status === 401 || error.response.status === 403) {
    return 'Vous devez être connecté avec les droits nécessaires pour accéder à cette ressource.';
  }

  return fallback;
};

export const getHttpErrorMessageAsync = async (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  if (!isAxiosError(error) || !(error.response?.data instanceof Blob)) {
    return getHttpErrorMessage(error, fallback);
  }

  try {
    const text = await error.response.data.text();
    const payload = JSON.parse(text) as { message?: unknown; error?: unknown; data?: { message?: unknown } };
    const apiMessage = payload.message ?? payload.error ?? payload.data?.message;

    if (typeof apiMessage === 'string' && apiMessage.trim() !== '') {
      return apiMessage.trim();
    }
  } catch {
    /* keep the standard fallback */
  }

  return getHttpErrorMessage(error, fallback);
};
