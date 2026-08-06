export const auditQueryKeys = {
  metadata: () => ['audits', 'metadata'] as const,
  mine: () => ['audits', 'mine'] as const,
  mineDetail: (id: number | null) => ['audits', 'mine-detail', id] as const,
  adminList: () => ['admin', 'audits'] as const,
  adminDetail: (id: number | null) => ['admin', 'audits', 'detail', id] as const,
};
