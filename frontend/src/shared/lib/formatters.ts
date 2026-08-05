const currencyFormatters = new Map<string, Intl.NumberFormat>();

const getCurrencyFormatter = (currency: string) => {
  const key = `fr-FR:${currency}`;
  const existing = currencyFormatters.get(key);
  if (existing) return existing;

  const formatter = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
  });
  currencyFormatters.set(key, formatter);

  return formatter;
};

export const formatCurrencyCents = (valueInCents: number, currency = 'EUR') =>
  getCurrencyFormatter(currency).format(valueInCents / 100);

export const formatEuroCents = (valueInCents: number) => formatCurrencyCents(valueInCents, 'EUR');

export const formatOptionalEuroCents = (valueInCents?: number | null) =>
  typeof valueInCents === 'number' ? formatEuroCents(valueInCents) : '-';

export const formatEuroCentsRange = (
  minValueInCents?: number | null,
  maxValueInCents?: number | null,
) => `${formatOptionalEuroCents(minValueInCents)} à ${formatOptionalEuroCents(maxValueInCents)}`;

export const formatEuroInputFromCents = (valueInCents?: number | null) =>
  typeof valueInCents === 'number' ? (valueInCents / 100).toFixed(2) : '';

export const parseEuroInputToCents = (value: string) => {
  const parsed = Number.parseFloat(value.replace(',', '.'));
  return Number.isFinite(parsed) ? Math.max(0, Math.round(parsed * 100)) : 0;
};

export const formatSchemaPriceCents = (valueInCents: number) => (valueInCents / 100).toFixed(2);

export const formatFrenchNumber = (value: number) => new Intl.NumberFormat('fr-FR').format(value);

export const formatFrenchDate = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

export const formatOptionalFrenchDate = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleDateString('fr-FR');
};

export const formatOptionalFrenchDateTime = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleString('fr-FR');
};

export const formatFrenchDateTime = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleString('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
};

export const formatFrenchDateTimeFull = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleString('fr-FR', {
    dateStyle: 'full',
    timeStyle: 'short',
  });
};

export const formatFrenchTime = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const formatDateInputForDisplay = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleDateString('fr-FR');
};
