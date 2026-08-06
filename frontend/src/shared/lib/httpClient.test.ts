import { describe, expect, it } from 'vitest';
import { AxiosError, AxiosHeaders } from 'axios';

import {
  ApiResponseError,
  createApiResponseError,
  getHttpErrorMessage,
  getHttpErrorMessageAsync,
  normalizeHttpError,
  shouldAttachIdempotencyKey,
  isCsrfFailureResponse,
  shouldAttachCsrfToken,
} from './httpClient';

describe('getHttpErrorMessage', () => {
  it('prefers the API message when present', () => {
    const error = new AxiosError('Request failed', '400', undefined, undefined, {
      data: { message: 'Message API precis.' },
      status: 400,
      statusText: 'Bad Request',
      headers: {},
      config: { headers: new AxiosHeaders() },
    });

    expect(getHttpErrorMessage(error)).toBe('Message API precis.');
  });

  it('returns a service availability message for network errors', () => {
    const error = new AxiosError('Network Error');

    expect(getHttpErrorMessage(error)).toContain('service est momentanément indisponible');
  });

  it('distinguishes request timeouts', () => {
    const error = new AxiosError('timeout', 'ECONNABORTED');

    expect(normalizeHttpError(error)).toMatchObject({
      kind: 'timeout',
      message: 'La requête a expiré. Vérifiez votre connexion puis réessayez.',
    });
  });

  it('distinguishes maintenance responses', () => {
    const error = new AxiosError('Request failed', '503', undefined, undefined, {
      data: {},
      status: 503,
      statusText: 'Service Unavailable',
      headers: {},
      config: { headers: new AxiosHeaders() },
    });

    expect(normalizeHttpError(error)).toMatchObject({
      kind: 'maintenance',
      message: 'Le service est temporairement en maintenance. Réessayez dans quelques instants.',
    });
  });

  it('hides technical backend details and keeps the request id', () => {
    const error = new AxiosError('Request failed', '500', undefined, undefined, {
      data: {
        error: {
          message: 'SQLSTATE[23000]: SELECT password FROM users',
          requestId: 'req_123',
        },
      },
      status: 500,
      statusText: 'Internal Server Error',
      headers: {},
      config: { headers: new AxiosHeaders() },
    });

    expect(getHttpErrorMessage(error)).toBe(
      'Le service rencontre un problème temporaire. Veuillez réessayer dans quelques instants. Référence : req_123',
    );
  });

  it('normalizes rate limit responses with Retry-After', () => {
    const error = new AxiosError('Request failed', '429', undefined, undefined, {
      data: { message: 'Trop de tentatives.' },
      status: 429,
      statusText: 'Too Many Requests',
      headers: { 'retry-after': '12' },
      config: { headers: new AxiosHeaders() },
    });

    expect(normalizeHttpError(error)).toMatchObject({
      kind: 'rate_limit',
      retryAfterSeconds: 12,
      status: 429,
    });
  });

  it('falls back to the frontend request id when the backend has no request id', () => {
    const headers = new AxiosHeaders();
    headers.set('X-Frontend-Request-Id', 'front_req_123');
    const error = new AxiosError('Request failed', '500', { headers }, undefined, {
      data: {},
      status: 500,
      statusText: 'Internal Server Error',
      headers: {},
      config: { headers: new AxiosHeaders() },
    });

    expect(normalizeHttpError(error)).toMatchObject({
      requestId: 'front_req_123',
    });
  });

  it('reads API messages from blob download errors', async () => {
    const error = new AxiosError('Request failed', '501', undefined, undefined, {
      data: new Blob([JSON.stringify({ message: 'Génération PDF accessible indisponible.' })], {
        type: 'application/json',
      }),
      status: 501,
      statusText: 'Not Implemented',
      headers: {},
      config: { headers: new AxiosHeaders() },
    });

    await expect(getHttpErrorMessageAsync(error)).resolves.toBe('Génération PDF accessible indisponible.');
  });
});

describe('createApiResponseError', () => {
  it('preserves the backend message and validation details', () => {
    const error = createApiResponseError({
      status: 'error',
      message: 'Le formulaire est invalide.',
      details: ['Le prix est obligatoire.'],
    });

    expect(error).toBeInstanceOf(ApiResponseError);
    expect(error?.message).toBe('Le formulaire est invalide.');
    expect(error?.details).toEqual(['Le prix est obligatoire.']);
  });

  it('reads the canonical nested API error envelope', () => {
    const error = createApiResponseError({
      error: {
        code: 'ORDER_NOT_PAYABLE',
        message: 'Cette commande ne peut plus être payée.',
        fields: { status: ['Statut invalide.'] },
        requestId: 'req_123',
      },
    });

    expect(error).toBeInstanceOf(ApiResponseError);
    expect(error?.code).toBe('ORDER_NOT_PAYABLE');
    expect(error?.message).toBe('Cette commande ne peut plus être payée.');
    expect(error?.fields).toEqual({ status: ['Statut invalide.'] });
    expect(error?.requestId).toBe('req_123');
  });

  it('ignores successful API envelopes', () => {
    expect(createApiResponseError({ status: 'success', data: {} })).toBeNull();
  });
});

describe('shouldAttachCsrfToken', () => {
  it('does not attach a CSRF token to public auth endpoints', () => {
    expect(shouldAttachCsrfToken('post', '/api/auth/register')).toBe(false);
    expect(shouldAttachCsrfToken('post', '/api/auth/login')).toBe(false);
    expect(shouldAttachCsrfToken('post', '/api/auth/refresh')).toBe(false);
  });

  it('attaches a CSRF token to protected unsafe API requests', () => {
    expect(shouldAttachCsrfToken('post', '/api/auth/profile')).toBe(true);
    expect(shouldAttachCsrfToken('post', '/api/auth/logout')).toBe(true);
  });

  it('detects explicit CSRF failures only', () => {
    expect(isCsrfFailureResponse(419)).toBe(true);
    expect(isCsrfFailureResponse(403, { message: 'Jeton CSRF invalide ou manquant.' })).toBe(true);
    expect(isCsrfFailureResponse(403, { message: 'Accès interdit.' })).toBe(false);
  });
});

describe('shouldAttachIdempotencyKey', () => {
  it('attaches idempotency keys only to unsafe methods', () => {
    expect(shouldAttachIdempotencyKey('get')).toBe(false);
    expect(shouldAttachIdempotencyKey('head')).toBe(false);
    expect(shouldAttachIdempotencyKey('post')).toBe(true);
    expect(shouldAttachIdempotencyKey('patch')).toBe(true);
    expect(shouldAttachIdempotencyKey('delete')).toBe(true);
  });
});
