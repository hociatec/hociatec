export const formatEuroCents = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(valueInCents / 100);

export const formatOptionalEuroCents = (valueInCents?: number | null) =>
  typeof valueInCents === 'number' ? formatEuroCents(valueInCents) : '-';

export const formatCurrencyCents = (valueInCents: number, currency = 'EUR') =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
  }).format((valueInCents ?? 0) / 100);

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
