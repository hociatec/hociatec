import { AxiosHeaders } from 'axios';

import { FRONTEND_REQUEST_ID_HEADER_NAME } from './http/headers';
import { parseNonNegativeInteger } from './parsers';
import { clampAtLeast } from './number';
import { isRecord } from './contractValidation';

export type AppErrorKind =
  | 'network'
  | 'offline'
  | 'timeout'
  | 'maintenance'
  | 'authentication'
  | 'authorization'
  | 'validation'
  | 'conflict'
  | 'rate_limit'
  | 'server'
  | 'unknown';

export const technicalMessagePatterns = [
  /stack trace/i,
  /sqlstate/i,
  /select\s+.+\s+from/i,
  /\/home\/|\/var\/|[A-Z]:\\/i,
  /Doctrine\\|Symfony\\/i,
];

export const safeMessage = (message: string | undefined, fallback: string) => {
  const normalized = message?.trim() ?? '';
  if (!normalized) return fallback;

  return technicalMessagePatterns.some((pattern) => pattern.test(normalized)) ? fallback : normalized;
};

export const retryAfterSeconds = (value: unknown) => {
  if (typeof value !== 'string' || value.trim() === '') return undefined;
  const numeric = parseNonNegativeInteger(value, Number.NaN);
  if (Number.isFinite(numeric)) return numeric;
  const date = Date.parse(value);
  if (Number.isNaN(date)) return undefined;

  return clampAtLeast(Math.ceil((date - Date.now()) / 1000), 0);
};

export const normalizeFields = (fields: unknown) =>
  isRecord(fields)
    ? Object.fromEntries(
        Object.entries(fields).map(([key, value]) => [
          key,
          Array.isArray(value) ? value.map(String) : [String(value)],
        ]),
      )
    : undefined;

export const resolveErrorKind = (status: number): AppErrorKind => {
  const exactKinds: Record<number, AppErrorKind> = {
    401: 'authentication',
    403: 'authorization',
    409: 'conflict',
    422: 'validation',
    429: 'rate_limit',
    503: 'maintenance',
  };

  return exactKinds[status] ?? (status >= 500 ? 'server' : 'unknown');
};

export const defaultMessages: Record<AppErrorKind, string> = {
  authentication: 'Vous devez être connecté avec les droits nécessaires pour accéder à cette ressource.',
  authorization: 'Vous devez être connecté avec les droits nécessaires pour accéder à cette ressource.',
  conflict: 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
  maintenance: 'Le service est temporairement en maintenance. Réessayez dans quelques instants.',
  network: 'Le service est momentanément indisponible. Vérifiez que le serveur API est démarré, puis réessayez.',
  offline: 'Vous semblez hors ligne. Vérifiez votre connexion puis réessayez.',
  rate_limit: 'Trop de tentatives. Patientez quelques instants avant de réessayer.',
  server: 'Le service rencontre un problème temporaire. Veuillez réessayer dans quelques instants.',
  timeout: 'La requête a expiré. Vérifiez votre connexion puis réessayez.',
  unknown: 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
  validation: 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
};

export const resolveDefaultMessage = (kind: AppErrorKind, status: number, fallback: string) => {
  if (status === 404) return 'La ressource demandée est introuvable.';
  if (kind === 'conflict' || kind === 'unknown' || kind === 'validation') return fallback;

  return defaultMessages[kind];
};

const isHeadersRecord = (value: unknown): value is Record<string, string | number | boolean | null> =>
  Boolean(value) && typeof value === 'object' && !Array.isArray(value);

export const getFrontendRequestId = (headers: unknown) => {
  const normalizedHeaders =
    headers instanceof AxiosHeaders || typeof headers === 'string' || isHeadersRecord(headers)
      ? new AxiosHeaders(headers)
      : new AxiosHeaders();
  const value = normalizedHeaders.get(FRONTEND_REQUEST_ID_HEADER_NAME);

  return typeof value === 'string' ? value : undefined;
};
