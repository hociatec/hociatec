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

export const formatServiceBillingMode = (value?: string | null) => {
  const normalized = value?.trim().toLowerCase();

  if (!normalized) {
    return 'Prix fixe';
  }

  return SERVICE_BILLING_MODE_LABELS[normalized] ?? value?.trim() ?? 'Prix fixe';
};
