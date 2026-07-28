import { readLocalStorage, removeLocalStorage, removeSessionStorage, writeLocalStorage } from './storage';

const CART_TOKEN_KEY = 'hociatec.cart.token';
const LEGACY_AUTH_TOKEN_KEY = 'hociatec.auth.token';
const LEGACY_AUTH_REFRESH_TOKEN_KEY = 'hociatec.auth.refresh.token';
const LEGACY_AUTH_SESSION_TOKEN_KEY = 'hociatec.auth.session.token';
const LEGACY_AUTH_SESSION_REFRESH_TOKEN_KEY = 'hociatec.auth.session.refresh.token';

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
