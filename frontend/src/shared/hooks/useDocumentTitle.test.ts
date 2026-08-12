import { describe, expect, it } from 'vitest';

import { formatDocumentTitle, normalizeDocumentTitle } from './useDocumentTitle';

describe('useDocumentTitle helpers', () => {
  it('formats a plain page title with a single hociatec suffix', () => {
    expect(formatDocumentTitle('Contact')).toBe('Contact — hociatec');
  });

  it('removes duplicated hociatec from the end before formatting', () => {
    expect(formatDocumentTitle('Contact — Hociatec')).toBe('Contact — hociatec');
    expect(formatDocumentTitle('Contact | hociatec')).toBe('Contact — hociatec');
  });

  it('removes hociatec when it appears at both the start and the end', () => {
    expect(formatDocumentTitle('Hociatec — Contact — hociatec')).toBe('Contact — hociatec');
  });

  it('normalizes the home page convention to keep hociatec only at the end', () => {
    expect(formatDocumentTitle('Hociatec — Informatique, réparation et services numériques')).toBe(
      'Informatique, réparation et services numériques — hociatec',
    );
  });

  it('falls back to the project title when nothing remains after normalization', () => {
    expect(normalizeDocumentTitle('Hociatec')).toBe('');
    expect(formatDocumentTitle('Hociatec')).toBe('hociatec');
  });
});
