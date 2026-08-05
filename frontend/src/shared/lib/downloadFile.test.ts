import { describe, expect, it } from 'vitest';

import { sanitizeCsvCell, sanitizeCsvText } from './downloadFile';

describe('CSV export hardening', () => {
  it('neutralizes spreadsheet formula injection prefixes', () => {
    expect(sanitizeCsvCell('=cmd|calc')).toBe("'=cmd|calc");
    expect(sanitizeCsvCell('+SUM(A1:A2)')).toBe("'+SUM(A1:A2)");
    expect(sanitizeCsvCell('-10')).toBe("'-10");
    expect(sanitizeCsvCell('@user')).toBe("'@user");
  });

  it('keeps regular CSV values unchanged', () => {
    expect(sanitizeCsvText('name,total\nHociatec,42')).toBe('name,total\nHociatec,42');
  });
});
