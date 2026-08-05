// @vitest-environment jsdom
import { http, HttpResponse } from 'msw';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { server } from '@/test/mswServer';
import {
  ApiResponseError,
  clearCartToken,
  clearCsrfToken,
  httpClient,
  persistCartToken,
} from './httpClient';

const capturedHeaders: Headers[] = [];

const captureHeaders = (request: Request) => {
  capturedHeaders.push(request.headers);
};

beforeEach(() => {
  vi.stubGlobal('crypto', { randomUUID: () => 'request-id-1' });
  clearCartToken();
  clearCsrfToken();
  capturedHeaders.length = 0;

  server.use(
    http.get('/api/csrf-token', () => HttpResponse.json({ data: { token: 'csrf-token-1' } })),
    http.post('/api/auth/refresh', () => HttpResponse.json({ data: { refreshed: true } })),
  );
});

afterEach(() => {
  clearCartToken();
  clearCsrfToken();
  vi.unstubAllGlobals();
});

describe('httpClient network conventions', () => {
  it('adds cart, CSRF and idempotency headers to unsafe protected requests', async () => {
    persistCartToken('cart-token-1');
    server.use(
      http.post('/api/protected-resource', ({ request }) => {
        captureHeaders(request);

        return HttpResponse.json({ data: { ok: true } });
      }),
    );

    await httpClient.post('/api/protected-resource', { name: 'Hociatec' });

    expect(capturedHeaders[0]?.get('x-cart-token')).toBe('cart-token-1');
    expect(capturedHeaders[0]?.get('x-csrf-token')).toBe('csrf-token-1');
    expect(capturedHeaders[0]?.get('idempotency-key')).toBe('request-id-1');
  });

  it('does not add CSRF to excluded auth endpoints', async () => {
    server.use(
      http.post('/api/auth/login', ({ request }) => {
        captureHeaders(request);

        return HttpResponse.json({ data: { message: 'Connecté.' } });
      }),
    );

    await httpClient.post('/api/auth/login', { email: 'admin@hociatec.fr', password: 'secret' });

    expect(capturedHeaders[0]?.has('x-csrf-token')).toBe(false);
    expect(capturedHeaders[0]?.get('idempotency-key')).toBe('request-id-1');
  });

  it('turns HTTP 200 business error envelopes into ApiResponseError', async () => {
    server.use(
      http.get('/api/business-error', () =>
        HttpResponse.json({
          error: {
            code: 'QUOTE_EXPIRED',
            message: 'Ce devis a expiré.',
            fields: { status: ['Statut invalide.'] },
            requestId: 'req_123',
          },
        }),
      ),
    );

    await expect(httpClient.get('/api/business-error')).rejects.toMatchObject({
      code: 'QUOTE_EXPIRED',
      fields: { status: ['Statut invalide.'] },
      message: 'Ce devis a expiré.',
      requestId: 'req_123',
    } satisfies Partial<ApiResponseError>);
  });

  it('refreshes once for simultaneous 401 responses then replays both requests', async () => {
    let refreshCount = 0;
    let protectedHits = 0;
    server.use(
      http.post('/api/auth/refresh', () => {
        refreshCount += 1;

        return HttpResponse.json({ data: { refreshed: true } });
      }),
      http.get('/api/protected-concurrent', () => {
        protectedHits += 1;

        if (protectedHits <= 2) {
          return HttpResponse.json({ message: 'Session expirée.' }, { status: 401 });
        }

        return HttpResponse.json({ data: { ok: true } });
      }),
    );

    await expect(
      Promise.all([
        httpClient.get('/api/protected-concurrent'),
        httpClient.get('/api/protected-concurrent'),
      ]),
    ).resolves.toHaveLength(2);

    expect(refreshCount).toBe(1);
    expect(protectedHits).toBe(4);
  });

  it('does not loop when refresh also fails with 401', async () => {
    let refreshCount = 0;
    let protectedHits = 0;
    server.use(
      http.post('/api/auth/refresh', () => {
        refreshCount += 1;

        return HttpResponse.json({ message: 'Session expirée.' }, { status: 401 });
      }),
      http.get('/api/protected-failure', () => {
        protectedHits += 1;

        return HttpResponse.json({ message: 'Session expirée.' }, { status: 401 });
      }),
    );

    await expect(httpClient.get('/api/protected-failure')).rejects.toMatchObject({
      response: { status: 401 },
    });

    expect(refreshCount).toBe(1);
    expect(protectedHits).toBe(1);
  });
});
