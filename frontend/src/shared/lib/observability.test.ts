// @vitest-environment jsdom
import { describe, expect, it } from 'vitest';

import { sanitizeObservabilityContext, toWebVitalPayload } from './observability';

describe('observability sanitization', () => {
  it('redacts sensitive context values before reporting', () => {
    expect(
      sanitizeObservabilityContext({
        authorization: 'Bearer secret',
        email: 'client@hociatec.fr',
        nested: {
          csrfToken: 'csrf-token',
          status: 500,
        },
        route: '/admin/orders',
      }),
    ).toEqual({
      authorization: '[redacted]',
      email: '[redacted]',
      nested: {
        csrfToken: '[redacted]',
        status: 500,
      },
      route: '/admin/orders',
    });
  });

  it('keeps web vital payloads free of form or user data', () => {
    const payload = toWebVitalPayload({
      delta: 123,
      entries: [],
      id: 'metric-1',
      name: 'LCP',
      navigationId: 1,
      navigationType: 'navigate',
      rating: 'good',
      value: 123,
    });

    expect(payload).toMatchObject({
      id: 'metric-1',
      name: 'LCP',
      rating: 'good',
      route: '/',
      value: 123,
    });
    expect(Object.keys(payload)).not.toContain('email');
    expect(Object.keys(payload)).not.toContain('message');
  });
});
