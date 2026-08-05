import { isAxiosError } from 'axios';

export type AppErrorKind =
  | 'network'
  | 'authentication'
  | 'authorization'
  | 'validation'
  | 'conflict'
  | 'rate_limit'
  | 'server'
  | 'unknown';

export interface AppError {
  kind: AppErrorKind;
  code?: string;
  message: string;
  fields?: Record<string, string[]>;
  requestId?: string;
  retryAfterSeconds?: number;
  status?: number;
}

export class ApiResponseError extends Error {
  readonly details: string[];
  readonly requestId?: string;
  readonly code?: string;

  constructor(message: string, details: string[] = [], requestId?: string, code?: string) {
    super(message);
    this.name = 'ApiResponseError';
    this.details = details;
    this.requestId = requestId;
    this.code = code;
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
  );
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
    return {
      kind: 'network',
      message:
        error.code === 'ECONNABORTED'
          ? 'La requête a expiré. Vérifiez votre connexion puis réessayez.'
          : 'Le service est momentanément indisponible. Vérifiez que le serveur API est démarré, puis réessayez.',
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

  const kind: AppErrorKind =
    status === 401
      ? 'authentication'
      : status === 403
        ? 'authorization'
        : status === 409
          ? 'conflict'
          : status === 422
            ? 'validation'
            : status === 429
              ? 'rate_limit'
              : status >= 500
                ? 'server'
                : 'unknown';

  const defaultMessage =
    kind === 'rate_limit'
      ? 'Trop de tentatives. Patientez quelques instants avant de réessayer.'
      : kind === 'server'
        ? 'Le service rencontre un problème temporaire. Veuillez réessayer dans quelques instants.'
        : kind === 'authentication' || kind === 'authorization'
          ? 'Vous devez être connecté avec les droits nécessaires pour accéder à cette ressource.'
          : status === 404
            ? 'La ressource demandée est introuvable.'
            : fallback;

  return {
    kind,
    code: typeof code === 'string' ? code : undefined,
    message: safeMessage(typeof message === 'string' ? message : undefined, defaultMessage),
    fields:
      fields && typeof fields === 'object' && !Array.isArray(fields)
        ? Object.fromEntries(
            Object.entries(fields as Record<string, unknown>).map(([key, value]) => [
              key,
              Array.isArray(value) ? value.map(String) : [String(value)],
            ]),
          )
        : undefined,
    requestId: typeof requestId === 'string' ? requestId : undefined,
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
