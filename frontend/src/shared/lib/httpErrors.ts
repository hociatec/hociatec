import { isAxiosError } from 'axios';

import {
  type AppErrorKind,
  defaultMessages,
  getFrontendRequestId,
  normalizeFields,
  resolveDefaultMessage,
  resolveErrorKind,
  retryAfterSeconds,
  safeMessage,
} from './httpErrorHelpers';

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
  }

  return getHttpErrorMessage(error, fallback);
};
