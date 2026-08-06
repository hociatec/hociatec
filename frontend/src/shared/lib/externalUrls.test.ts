import { describe, expect, it, vi } from 'vitest';

import { openMailtoClient, toSafeAttachmentUrl, toSafeHttpsUrl } from './externalUrls';

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

  it('builds a safe mailto URL and opens the client', () => {
    const setLocation = vi.spyOn(window.location, 'href', 'set');

    openMailtoClient('Jean.Dupont+test@exemple.fr', 'Bonjour', 'Lien : https://hociatec.fr');

    expect(setLocation).toHaveBeenCalledTimes(1);
    const calledWith = setLocation.mock.calls[0]![0] as string;
    expect(calledWith).toContain('mailto:Jean.Dupont%2Btest%40exemple.fr');
    expect(calledWith).toContain('subject=Bonjour');
    expect(calledWith).toContain('body=Lien%20%3A%20https%3A%2F%2Fhociatec.fr');
  });

  it('does nothing when recipient is empty', () => {
    const setLocation = vi.spyOn(window.location, 'href', 'set');

    openMailtoClient('   ', 'Bonjour', 'Test');
    expect(setLocation).not.toHaveBeenCalled();
  });
});
