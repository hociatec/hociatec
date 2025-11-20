import axios, { AxiosHeaders } from 'axios';

import { API_BASE_URL } from '../config/appConfig';

const AUTH_TOKEN_KEY = 'hociatec.auth.token';
const CART_TOKEN_KEY = 'hociatec.cart.token';

const hasWindow = typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';

const readStorage = (key: string) => {
  if (!hasWindow) return null;
  try {
    return window.localStorage.getItem(key);
  } catch {
    return null;
  }
};

const writeStorage = (key: string, value: string) => {
  if (!hasWindow) return;
  try {
    window.localStorage.setItem(key, value);
  } catch {
    /* noop */
  }
};

const removeStorage = (key: string) => {
  if (!hasWindow) return;
  try {
    window.localStorage.removeItem(key);
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

  const authToken = readStorage(AUTH_TOKEN_KEY);
  if (authToken && !headers.has('Authorization')) {
    headers.set('Authorization', `Bearer ${authToken}`);
  }

  const cartToken = readStorage(CART_TOKEN_KEY);
  if (cartToken && !headers.has('X-Cart-Token')) {
    headers.set('X-Cart-Token', cartToken);
  }

  config.headers = headers;

  return config;
});

export const persistAuthToken = (token: string) => writeStorage(AUTH_TOKEN_KEY, token);

export const clearAuthToken = () => removeStorage(AUTH_TOKEN_KEY);

export const getPersistedToken = () => readStorage(AUTH_TOKEN_KEY);

export const persistCartToken = (token: string) => writeStorage(CART_TOKEN_KEY, token);

export const clearCartToken = () => removeStorage(CART_TOKEN_KEY);

export const getPersistedCartToken = () => readStorage(CART_TOKEN_KEY);
