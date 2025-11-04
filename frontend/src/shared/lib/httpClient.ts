import axios, { AxiosHeaders } from 'axios';

import { API_BASE_URL } from '../config/appConfig';

const AUTH_TOKEN_KEY = 'hociatec.auth.token';
const CART_TOKEN_KEY = 'hociatec.cart.token';

export const httpClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

httpClient.interceptors.request.use((config) => {
  const headers =
    config.headers instanceof AxiosHeaders ? config.headers : new AxiosHeaders(config.headers);

  const authToken = localStorage.getItem(AUTH_TOKEN_KEY);
  if (authToken && !headers.has('Authorization')) {
    headers.set('Authorization', `Bearer ${authToken}`);
  }

  const cartToken = localStorage.getItem(CART_TOKEN_KEY);
  if (cartToken && !headers.has('X-Cart-Token')) {
    headers.set('X-Cart-Token', cartToken);
  }

  config.headers = headers;

  return config;
});

export const persistAuthToken = (token: string) =>
  localStorage.setItem(AUTH_TOKEN_KEY, token);

export const clearAuthToken = () => localStorage.removeItem(AUTH_TOKEN_KEY);

export const getPersistedToken = () => localStorage.getItem(AUTH_TOKEN_KEY);

export const persistCartToken = (token: string) =>
  localStorage.setItem(CART_TOKEN_KEY, token);

export const clearCartToken = () => localStorage.removeItem(CART_TOKEN_KEY);

export const getPersistedCartToken = () => localStorage.getItem(CART_TOKEN_KEY);
