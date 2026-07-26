import { describe, expect, it } from 'vitest';
import { AxiosError, AxiosHeaders } from 'axios';

import { ApiResponseError, createApiResponseError, getHttpErrorMessage, getHttpErrorMessageAsync, shouldAttachCsrfToken } from './httpClient';

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

  it('ignores successful API envelopes', () => {
    expect(createApiResponseError({ status: 'success', data: {} })).toBeNull();
  });
});

describe('shouldAttachCsrfToken', () => {
  it('does not attach a CSRF token to public auth endpoints', () => {
    expect(shouldAttachCsrfToken('post', '/api/auth/register')).toBe(false);
    expect(shouldAttachCsrfToken('post', '/api/auth/login')).toBe(false);
    expect(shouldAttachCsrfToken('post', '/api/auth/refresh')).toBe(false);
    expect(shouldAttachCsrfToken('post', '/api/auth/logout')).toBe(false);
  });

  it('attaches a CSRF token to protected unsafe API requests', () => {
    expect(shouldAttachCsrfToken('post', '/api/auth/profile')).toBe(true);
  });
});
