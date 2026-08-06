import { AxiosHeaders, isAxiosError } from 'axios';

import { FRONTEND_REQUEST_ID_HEADER_NAME } from './http/headers';

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

export interface AppError {
  kind: AppErrorKind;
  code?: string | undefined;
  message: string;
  fields?: Record<string, string[]> | undefined;
  requestId?: string | undefined;
  retryAfterSeconds?: number | undefined;
  status?: number | undefined;
}

export class ApiResponseError extends Error {
  readonly details: string[];
  readonly fields?: Record<string, string[]> | undefined;
  readonly requestId?: string | undefined;
  readonly code?: string | undefined;

  constructor(
    message: string,
    details: string[] = [],
    requestId?: string,
    code?: string,
    fields?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiResponseError';
    this.details = details;
    this.requestId = requestId;
    this.code = code;
    this.fields = fields;
  }
}

const technicalMessagePatterns = [
  /stack trace/i,
  /sqlstate/i,
  /select\s+.+\s+from/i,
  /\/home\/|\/var\/|[A-Z]:\\/i,
  /Doctrine\\|Symfony\\/i,
];

const safeMessage = (message: string | undefined, fallback: string) => {
  const normalized = message?.trim() ?? '';
  if (!normalized) return fallback;

  return technicalMessagePatterns.some((pattern) => pattern.test(normalized)) ? fallback : normalized;
};

const retryAfterSeconds = (value: unknown) => {
  if (typeof value !== 'string' || value.trim() === '') return undefined;
  const numeric = Number.parseInt(value, 10);
  if (Number.isFinite(numeric)) return Math.max(0, numeric);
  const date = Date.parse(value);
  if (Number.isNaN(date)) return undefined;

  return Math.max(0, Math.ceil((date - Date.now()) / 1000));
};

export const createApiResponseError = (payload: unknown): ApiResponseError | null => {
  if (!payload || typeof payload !== 'object') return null;
  const response = payload as {
    status?: unknown;
    message?: unknown;
    details?: unknown;
    error?: unknown;
    requestId?: unknown;
    code?: unknown;
  };
  const nestedError =
    response.error && typeof response.error === 'object'
      ? (response.error as { message?: unknown; details?: unknown; requestId?: unknown; code?: unknown })
      : null;
  const message = response.message ?? nestedError?.message;
  const details = response.details ?? nestedError?.details;
  const fields =
    nestedError && 'fields' in nestedError
      ? (nestedError as { fields?: unknown }).fields
      : undefined;
  if (response.status !== 'error' && !nestedError) return null;
  if (typeof message !== 'string') return null;

  return new ApiResponseError(
    safeMessage(message, 'Une erreur est survenue.'),
    Array.isArray(details) ? details.map(String) : [],
    typeof (response.requestId ?? nestedError?.requestId) === 'string'
      ? String(response.requestId ?? nestedError?.requestId)
      : undefined,
    typeof (response.code ?? nestedError?.code) === 'string'
      ? String(response.code ?? nestedError?.code)
      : undefined,
    normalizeFields(fields),
  );
};

const normalizeFields = (fields: unknown) =>
  fields && typeof fields === 'object' && !Array.isArray(fields)
    ? Object.fromEntries(
        Object.entries(fields as Record<string, unknown>).map(([key, value]) => [
          key,
          Array.isArray(value) ? value.map(String) : [String(value)],
        ]),
      )
    : undefined;

const resolveErrorKind = (status: number): AppErrorKind => {
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

const defaultMessages: Record<AppErrorKind, string> = {
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

const resolveDefaultMessage = (kind: AppErrorKind, status: number, fallback: string) => {
  if (status === 404) return 'La ressource demandée est introuvable.';
  if (kind === 'conflict' || kind === 'unknown' || kind === 'validation') return fallback;

  return defaultMessages[kind];
};

const isHeadersRecord = (value: unknown): value is Record<string, string | number | boolean | null> =>
  Boolean(value) && typeof value === 'object' && !Array.isArray(value);

const getFrontendRequestId = (headers: unknown) => {
  const normalizedHeaders =
    headers instanceof AxiosHeaders || typeof headers === 'string' || isHeadersRecord(headers)
      ? new AxiosHeaders(headers)
      : new AxiosHeaders();
  const value = normalizedHeaders.get(FRONTEND_REQUEST_ID_HEADER_NAME);

  return typeof value === 'string' ? value : undefined;
};

export const normalizeHttpError = (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
): AppError => {
  if (error instanceof ApiResponseError) {
    return {
      kind: 'unknown',
      code: error.code,
      message: safeMessage(error.message, fallback),
      fields: error.fields,
      requestId: error.requestId,
    };
  }

  if (!isAxiosError(error)) {
    return {
      kind: 'unknown',
      message: error instanceof Error ? safeMessage(error.message, fallback) : fallback,
    };
  }

  if (!error.response) {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
      return {
        kind: 'offline',
        message: defaultMessages.offline,
      };
    }

    if (error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT') {
      return {
        kind: 'timeout',
        message: defaultMessages.timeout,
      };
    }

    return {
      kind: 'network',
      message: defaultMessages.network,
    };
  }

  const status = error.response.status;
  const responseData = error.response.data as
    | {
        message?: unknown;
        error?: { message?: unknown; code?: unknown; fields?: unknown; requestId?: unknown };
        requestId?: unknown;
        code?: unknown;
        fields?: unknown;
      }
    | undefined;
  const message = responseData?.error?.message ?? responseData?.message;
  const requestId = responseData?.error?.requestId ?? responseData?.requestId;
  const code = responseData?.error?.code ?? responseData?.code;
  const fields = responseData?.error?.fields ?? responseData?.fields;

  const kind = resolveErrorKind(status);
  const defaultMessage = resolveDefaultMessage(kind, status, fallback);

  return {
    kind,
    code: typeof code === 'string' ? code : undefined,
    message: safeMessage(typeof message === 'string' ? message : undefined, defaultMessage),
    fields: normalizeFields(fields),
    requestId: typeof requestId === 'string' ? requestId : getFrontendRequestId(error.config?.headers),
    retryAfterSeconds: retryAfterSeconds(error.response.headers?.['retry-after']),
    status,
  };
};

export const getHttpErrorMessage = (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  const normalized = normalizeHttpError(error, fallback);
  return normalized.requestId
    ? `${normalized.message} Référence : ${normalized.requestId}`
    : normalized.message;
};

export const getHttpErrorMessageAsync = async (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  if (!isAxiosError(error) || !(error.response?.data instanceof Blob)) {
    return getHttpErrorMessage(error, fallback);
  }

  try {
    const payload = JSON.parse(await error.response.data.text()) as {
      message?: unknown;
      error?: unknown;
      data?: { message?: unknown };
    };
    const apiMessage = payload.message ?? payload.error ?? payload.data?.message;
    if (typeof apiMessage === 'string' && apiMessage.trim() !== '') return apiMessage.trim();
  } catch {
    // Le message HTTP standard reste le fallback.
  }

  return getHttpErrorMessage(error, fallback);
};
