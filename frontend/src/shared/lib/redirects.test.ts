import { describe, expect, it } from 'vitest';

import { isSafeInternalRedirectPath, isTrustedRedirectUrl } from './redirects';

describe('isSafeInternalRedirectPath', () => {
  it('accepts relative application paths', () => {
    expect(isSafeInternalRedirectPath('/mon-espace/commandes?tab=paid')).toBe(true);
  });

  it('rejects external, protocol-relative and auth-loop paths', () => {
    expect(isSafeInternalRedirectPath('https://example.com')).toBe(false);
    expect(isSafeInternalRedirectPath('//example.com/path')).toBe(false);
    expect(isSafeInternalRedirectPath('/login')).toBe(false);
    expect(isSafeInternalRedirectPath('/register')).toBe(false);
    expect(isSafeInternalRedirectPath(null)).toBe(false);
  });
});

describe('isTrustedRedirectUrl', () => {
  it('accepts trusted HTTPS checkout URLs', () => {
    expect(isTrustedRedirectUrl('https://checkout.stripe.com/c/pay/session')).toBe(true);
  });

  it('rejects untrusted, non-HTTPS and malformed redirects', () => {
    expect(isTrustedRedirectUrl('https://evil.example/pay')).toBe(false);
    expect(isTrustedRedirectUrl('http://checkout.stripe.com/pay')).toBe(false);
    expect(isTrustedRedirectUrl('not a url')).toBe(false);
  });
});
