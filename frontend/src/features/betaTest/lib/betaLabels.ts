export const betaProfileStatusLabels: Record<string, string> = {
  pending: 'En attente',
  accepted: 'Accepté',
  paused: 'En pause',
  rejected: 'Refusé',
};

export const bugReportStatusLabels: Record<string, string> = {
  submitted: 'Soumis',
  under_review: "En cours d’analyse",
  resolved: 'Corrigé',
  closed: 'Fermé',
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

export const accessibilityNeedLabels: Record<string, string> = {
  blind: 'Non-voyant ou utilisation principale d’un lecteur d’écran',
  low_vision: 'Malvoyant ou besoin d’un affichage adapté',
  none: 'Pas de besoin d’accessibilité spécifique',
};

export const formatBetaLabel = (value?: string | null, labels?: Record<string, string>) => {
  if (!value) return 'Non renseigné';

  return labels?.[value] ?? value;
};

export const formatBetaList = (values?: string[]) =>
  values && values.length > 0 ? values.join(', ') : 'Non renseigné';

export const formatDate = (value?: string | null) =>
  value ? new Date(value).toLocaleDateString('fr-FR') : 'Non définie';
