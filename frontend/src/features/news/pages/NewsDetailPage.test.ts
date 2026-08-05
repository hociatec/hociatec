import { describe, expect, it } from 'vitest';

import { normalizeSlugFromUrl } from './NewsDetailPage';

describe('normalizeSlugFromUrl', () => {
  it('decodes and trims a valid URL slug', () => {
    expect(normalizeSlugFromUrl('%22actualite-test%22')).toBe('actualite-test');
  });

  it('keeps malformed URL encoding from crashing the news route', () => {
    expect(normalizeSlugFromUrl('actualite-test%')).toBe('actualite-test%');
  });
});
