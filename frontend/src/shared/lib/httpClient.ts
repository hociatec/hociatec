import axios, { AxiosHeaders, isAxiosError } from 'axios';

import { API_BASE_URL } from '../config/appConfig';

const AUTH_TOKEN_KEY = 'hociatec.auth.token';
const AUTH_SESSION_TOKEN_KEY = 'hociatec.auth.session.token';
const AUTH_REFRESH_TOKEN_KEY = 'hociatec.auth.refresh.token';
const AUTH_SESSION_REFRESH_TOKEN_KEY = 'hociatec.auth.session.refresh.token';
const CART_TOKEN_KEY = 'hociatec.cart.token';

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

const readSessionStorage = (key: string) => {
  if (!hasSessionStorage) return null;
  try {
    return window.sessionStorage.getItem(key);
  } catch {
    return null;
  }
};

const writeSessionStorage = (key: string, value: string) => {
  if (!hasSessionStorage) return;
  try {
    window.sessionStorage.setItem(key, value);
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
  headers: {
    'Content-Type': 'application/json',
  },
});

httpClient.interceptors.request.use((config) => {
  const headers =
    config.headers instanceof AxiosHeaders ? config.headers : new AxiosHeaders(config.headers);

  const authToken = readLocalStorage(AUTH_TOKEN_KEY) ?? readSessionStorage(AUTH_SESSION_TOKEN_KEY);
  if (authToken && !headers.has('Authorization')) {
    headers.set('Authorization', `Bearer ${authToken}`);
  }

  const cartToken = readLocalStorage(CART_TOKEN_KEY);
  if (cartToken && !headers.has('X-Cart-Token')) {
    headers.set('X-Cart-Token', cartToken);
  }

  config.headers = headers;

  return config;
});

export const persistAuthToken = (token: string, remember = false) => {
  if (remember) {
    writeLocalStorage(AUTH_TOKEN_KEY, token);
    removeSessionStorage(AUTH_SESSION_TOKEN_KEY);
    return;
  }

  writeSessionStorage(AUTH_SESSION_TOKEN_KEY, token);
  removeLocalStorage(AUTH_TOKEN_KEY);
};

export const persistRefreshToken = (token: string, remember = false) => {
  if (remember) {
    writeLocalStorage(AUTH_REFRESH_TOKEN_KEY, token);
    removeSessionStorage(AUTH_SESSION_REFRESH_TOKEN_KEY);
    return;
  }

  writeSessionStorage(AUTH_SESSION_REFRESH_TOKEN_KEY, token);
  removeLocalStorage(AUTH_REFRESH_TOKEN_KEY);
};

export const clearAuthToken = () => {
  removeLocalStorage(AUTH_TOKEN_KEY);
  removeSessionStorage(AUTH_SESSION_TOKEN_KEY);
  removeLocalStorage(AUTH_REFRESH_TOKEN_KEY);
  removeSessionStorage(AUTH_SESSION_REFRESH_TOKEN_KEY);
};

export const getPersistedToken = () => readLocalStorage(AUTH_TOKEN_KEY) ?? readSessionStorage(AUTH_SESSION_TOKEN_KEY);

export const getPersistedRefreshToken = () =>
  readLocalStorage(AUTH_REFRESH_TOKEN_KEY) ?? readSessionStorage(AUTH_SESSION_REFRESH_TOKEN_KEY);

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
