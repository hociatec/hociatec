import { normalizeSearchText } from '@/shared/lib/searchText';

const SERVICE_BILLING_MODE_LABELS: Record<string, string> = {
  'prix fixe': 'Prix fixe',
  heure: 'Horaire',
  horaire: 'Horaire',
  jour: 'À la journée',
  intervention: 'Par intervention',
  audit: 'Audit',
  installation: 'Installation',
  maintenance: 'Maintenance',
};

export const normalizeServiceBillingMode = (value?: string | null) => {
  const normalized = normalizeSearchText(value);

  if (!normalized) {
    return 'prix fixe';
  }

  if (normalized === 'heure') {
    return 'horaire';
  }

  return normalized;
};

export const formatServiceBillingMode = (value?: string | null) => {
  const normalized = normalizeServiceBillingMode(value);
  return SERVICE_BILLING_MODE_LABELS[normalized] ?? value?.trim() ?? 'Prix fixe';
};
