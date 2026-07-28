export const terminalStates = new Set(['resolved', 'duplicate', 'rejected']);

export const bugReportBadgeClassName = (value: string) => {
  if (['resolved'].includes(value)) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  if (['critical', 'high', 'rejected'].includes(value)) return 'bg-red-50 text-red-700 ring-red-200';
  if (['submitted', 'under_review', 'need_info', 'planned'].includes(value)) return 'bg-amber-50 text-amber-700 ring-amber-200';
  return 'bg-stone-50 text-stone-700 ring-stone-200';
};

export const activityLabel = (action: string) => ({
  status_changed: 'État modifié',
  assignment_changed: 'Responsable modifié',
  marked_duplicate: 'Doublon rattaché',
  comment_added: 'Message ajouté',
  report_created: 'Signalement créé',
}[action] ?? action);
