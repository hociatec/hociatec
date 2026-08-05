import { describe, expect, it } from 'vitest';

import {
  PRIVATE_ROBOTS_CONTENT,
  resolveStaticRouteSeo,
  toAbsoluteSiteUrl,
} from './seoConfig';

describe('seoConfig', () => {
  it('resolves relative URLs against the public site URL', () => {
    expect(toAbsoluteSiteUrl('/catalogue/vente')).toBe('https://hociatec.fr/catalogue/vente');
    expect(toAbsoluteSiteUrl('https://example.com/image.png')).toBe('https://example.com/image.png');
  });

  it('marks search pages as noindex in static metadata', () => {
    expect(resolveStaticRouteSeo('/recherche')?.robots).toBe(PRIVATE_ROBOTS_CONTENT);
    expect(resolveStaticRouteSeo('/catalogue/recherche')?.robots).toBe(PRIVATE_ROBOTS_CONTENT);
  });
});
