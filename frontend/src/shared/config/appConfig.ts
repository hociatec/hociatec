export const PROJECT_TITLE = 'hociatec';

export type AppEnv = 'development' | 'staging' | 'production' | 'test';

export interface BuildInfo {
  frontendVersion: string;
  commitSha: string;
  buildDate: string;
  environment: AppEnv;
}

const APP_ENVS = new Set<AppEnv>(['development', 'staging', 'production', 'test']);

const normalizeApiBaseUrl = (value: string | undefined) => {
  const normalized = value?.trim().replace(/\/+$/, '') ?? '';
  if (normalized === '') return '';
  if (normalized.startsWith('/') && !normalized.startsWith('//')) return normalized;

  try {
    return new URL(normalized).toString().replace(/\/+$/, '');
  } catch {
    throw new Error('VITE_API_BASE_URL doit être vide, relatif ou être une URL absolue valide.');
  }
};

const resolveAppEnv = (value: string | undefined): AppEnv => {
  const normalized = value?.trim() || import.meta.env.MODE || 'development';
  if (APP_ENVS.has(normalized as AppEnv)) return normalized as AppEnv;

  throw new Error('VITE_APP_ENV doit valoir development, staging, production ou test.');
};

export const APP_ENV = resolveAppEnv(import.meta.env.VITE_APP_ENV);
export const API_BASE_URL = normalizeApiBaseUrl(import.meta.env.VITE_API_BASE_URL);
export const BUILD_INFO: BuildInfo = {
  frontendVersion: import.meta.env.VITE_APP_VERSION || '0.0.0',
  commitSha: import.meta.env.VITE_COMMIT_SHA || 'unknown',
  buildDate: import.meta.env.VITE_BUILD_DATE || 'unknown',
  environment: APP_ENV,
};
