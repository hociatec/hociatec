import { describe, expect, it } from 'vitest';

import {
  formatApiDateForDateInput,
  formatApiDateForDateTimeInput,
  formatFrenchDateTimeWithZone,
  formatOptionalFrenchDateTime,
  parseApiDate,
} from './formatters';

describe('date formatters', () => {
  it('keeps API date-only values stable for date inputs', () => {
    expect(formatApiDateForDateInput('2026-08-05')).toBe('2026-08-05');
    expect(formatApiDateForDateInput('2026-08-05T01:30:00Z')).toBe('2026-08-05');
  });

  it('normalizes API datetimes for datetime-local inputs', () => {
    expect(formatApiDateForDateTimeInput('2026-08-05T01:30:00Z')).toBe('2026-08-05T01:30');
  });

  it('rejects invalid API dates', () => {
    expect(parseApiDate('05/08/2026 03:30')).toBeNull();
    expect(formatOptionalFrenchDateTime('not-a-date')).toBe('-');
  });

  it('formats user-facing datetimes with the Paris time zone', () => {
    expect(formatFrenchDateTimeWithZone('2026-08-05T12:30:00Z')).toContain('heure');
  });
});
