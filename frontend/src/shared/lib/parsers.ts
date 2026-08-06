export const parseDecimalInput = (value: string, fallback: number = Number.NaN): number => {
  const normalized = value.trim().replace(',', '.');

  if (normalized === '') {
    return fallback;
  }

  const parsed = Number.parseFloat(normalized);

  return Number.isFinite(parsed) ? parsed : fallback;
};

export const parseNonNegativeDecimal = (value: string | undefined | null, fallback: number = Number.NaN): number => {
  const parsed = parseDecimalInput(String(value ?? ''), fallback);

  return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
};

export const parseNullableNonNegativeDecimal = (
  value: string | undefined | null,
  fallback: number = Number.NaN,
): number | null => {
  const parsed = parseNonNegativeDecimal(value, fallback);

  return Number.isFinite(parsed) ? parsed : null;
};

export const parseIntegerInput = (value: string, fallback: number = Number.NaN): number => {
  const normalized = value.trim().replace(',', '.');

  if (normalized === '') {
    return fallback;
  }

  const parsed = Number.parseInt(normalized, 10);

  return Number.isFinite(parsed) ? parsed : fallback;
};

export const parseNullableInteger = (value: string | undefined | null): number | null => {
  const parsed = parseIntegerInput(String(value ?? ''), Number.NaN);

  return Number.isFinite(parsed) ? parsed : null;
};

export const parseNonNegativeInteger = (value: string | undefined | null, fallback: number = Number.NaN): number => {
  const parsed = parseIntegerInput(String(value ?? ''), fallback);

  return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
};

export const parseNullableNonNegativeInteger = (value: string | undefined | null): number | null => {
  const parsed = parseNonNegativeInteger(value, Number.NaN);

  return Number.isFinite(parsed) ? parsed : null;
};

const parsePositiveInteger = (value: string | undefined | null, fallback: number = Number.NaN): number => {
  const parsed = parseIntegerInput(String(value ?? ''), fallback);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

export const parseNullablePositiveInteger = (value: string | undefined | null): number | null => {
  const parsed = parsePositiveInteger(value, Number.NaN);

  return Number.isFinite(parsed) ? parsed : null;
};
