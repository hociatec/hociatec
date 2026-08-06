import { logger } from '../logger';

const hasLocalStorage = typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
const hasSessionStorage =
  typeof window !== 'undefined' && typeof window.sessionStorage !== 'undefined';

export const readLocalStorage = (key: string) => {
  if (!hasLocalStorage) return null;
  try {
    return window.localStorage.getItem(key);
  } catch (error) {
    logger.warn('Unable to read localStorage.', { error });
    return null;
  }
};

export const writeLocalStorage = (key: string, value: string) => {
  if (!hasLocalStorage) return;
  try {
    window.localStorage.setItem(key, value);
  } catch (error) {
    logger.warn('Unable to write localStorage.', { error });
  }
};

export const removeLocalStorage = (key: string) => {
  if (!hasLocalStorage) return;
  try {
    window.localStorage.removeItem(key);
  } catch (error) {
    logger.warn('Unable to remove localStorage item.', { error });
  }
};

export const removeSessionStorage = (key: string) => {
  if (!hasSessionStorage) return;
  try {
    window.sessionStorage.removeItem(key);
  } catch (error) {
    logger.warn('Unable to remove sessionStorage item.', { error });
  }
};

export const readSessionStorage = (key: string) => {
  if (!hasSessionStorage) return null;
  try {
    return window.sessionStorage.getItem(key);
  } catch (error) {
    logger.warn('Unable to read sessionStorage.', { error });
    return null;
  }
};

export const writeSessionStorage = (key: string, value: string) => {
  if (!hasSessionStorage) return;
  try {
    window.sessionStorage.setItem(key, value);
  } catch (error) {
    logger.warn('Unable to write sessionStorage item.', { error });
  }
};
