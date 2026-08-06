import { parseNonNegativeDecimal } from '@/shared/lib/parsers';

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

const parseBoolean = (value: string | undefined, fallback: boolean) => {
  if (value === undefined || value.trim() === '') return fallback;

  return value === 'true' || value === '1';
};

const parseSampleRate = (value: string | undefined, fallback: number) => {
  if (value === undefined || value.trim() === '') return fallback;
  const parsed = parseNonNegativeDecimal(value, Number.NaN);

  if (!Number.isFinite(parsed) || parsed < 0 || parsed > 1) {
    throw new Error('Les taux observabilité doivent être compris entre 0 et 1.');
  }

  return parsed;
};

export const OBSERVABILITY_CONFIG = {
  enabled: parseBoolean(import.meta.env.VITE_OBSERVABILITY_ENABLED, APP_ENV === 'production'),
  sentryDsn: import.meta.env.VITE_SENTRY_DSN?.trim() || '',
  tracesSampleRate: parseSampleRate(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE, 0.05),
  webVitalsEndpoint: import.meta.env.VITE_WEB_VITALS_ENDPOINT?.trim() || '',
};
