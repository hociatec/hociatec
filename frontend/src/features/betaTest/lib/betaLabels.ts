export const betaProfileStatusLabels: Record<string, string> = {
  pending: 'En attente',
  accepted: 'Accepté',
  paused: 'En pause',
  rejected: 'Refusé',
};

export const bugReportStatusLabels: Record<string, string> = {
  submitted: 'Soumis',
  under_review: "En cours d’analyse",
  need_info: 'Informations nécessaires',
  planned: 'Correction planifiée',
  resolved: 'Corrigé',
  duplicate: 'Doublon',
  rejected: 'Rejeté',
};

export const campaignStateLabels: Record<string, string> = {
  draft: 'Brouillon',
  active: 'Active',
  closed: 'Clôturée',
};

export const severityLabels: Record<string, string> = {
  low: 'Faible',
  normal: 'Normale',
  high: 'Haute',
  critical: 'Critique',
};

export const formatBetaLabel = (value?: string | null, labels?: Record<string, string>) => {
  if (!value) return 'Non renseigné';

  return labels?.[value] ?? value;
};

export const formatBetaList = (values?: string[]) =>
  values && values.length > 0 ? values.join(', ') : 'Non renseigné';

export const formatDate = (value?: string | null) =>
  value ? formatOptionalFrenchDate(value) : 'Non définie';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
