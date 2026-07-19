import { describe, expect, it } from 'vitest';
import { AxiosError, AxiosHeaders } from 'axios';

import { getHttpErrorMessage } from './httpClient';

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
});
