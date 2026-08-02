export const PROJECT_TITLE = 'hociatec';

const resolveDefaultApiBaseUrl = () => {
  if (typeof window === 'undefined') {
    return '/';
  }

  const { origin, hostname } = window.location;
  if (hostname === 'hociatec.fr' || hostname === 'www.hociatec.fr') {
    return 'https://api.hociatec.fr';
  }

  if (hostname === 'localhost' || hostname === '127.0.0.1') {
    return 'http://localhost:8000';
  }

  return origin;
};

const resolveEnvValue = () => {
  try {
    if (typeof import.meta !== 'undefined' && import.meta.env) {
      return import.meta.env.VITE_API_BASE_URL;
    }
  } catch {
    /* noop */
  }

  const globalProcess =
    typeof globalThis !== 'undefined'
      ? (globalThis as { process?: { env?: Record<string, string | undefined> } }).process
      : undefined;
  const envValue = globalProcess?.env?.VITE_API_BASE_URL;
  if (envValue) return envValue;

  return undefined;
};

export const API_BASE_URL = resolveEnvValue() ?? resolveDefaultApiBaseUrl();
