// @vitest-environment jsdom
import { describe, expect, it } from 'vitest';

import { toSafeAttachmentUrl, toSafeHttpsUrl, toSafeMailtoUrl } from './externalUrls';

describe('external URL safety', () => {
  it('accepts HTTPS URLs and relative resource URLs', () => {
    expect(toSafeHttpsUrl('https://example.com/suivi')).toBe('https://example.com/suivi');
    expect(toSafeAttachmentUrl('/uploads/capture.png', 'https://api.hociatec.fr')).toBe(
      'https://api.hociatec.fr/uploads/capture.png',
    );
  });

  it('rejects unsafe protocols and malformed values', () => {
    expect(toSafeHttpsUrl('javascript:alert(1)')).toBeNull();
    expect(toSafeHttpsUrl('http://example.com/suivi')).toBeNull();
    expect(toSafeHttpsUrl('not a url', '')).toBeNull();
  });

  it('builds a safe mailto URL', () => {
    const calledWith = toSafeMailtoUrl(
      'Jean.Dupont+test@exemple.fr',
      'Bonjour',
      'Lien : https://hociatec.fr',
    );

    expect(calledWith).toContain('mailto:Jean.Dupont%2Btest%40exemple.fr');
    expect(calledWith).toContain('subject=Bonjour');
    expect(calledWith).toContain('body=Lien%20%3A%20https%3A%2F%2Fhociatec.fr');
  });

  it('returns null when recipient is empty', () => {
    expect(toSafeMailtoUrl('   ', 'Bonjour', 'Test')).toBeNull();
  });
});
