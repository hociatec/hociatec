import { useEffect, useMemo, useRef, useState } from 'react';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/api/auditsApi';

export const AUDIT_TYPES = [
  'all',
  'accessibility',
  'performance',
  'security',
  'ux',
  'seo',
  'technical',
] as const;
export const AUDIT_STATUSES = ['all', 'new', 'in_progress', 'review', 'done'] as const;
export const AUDIT_SORTS = [
  'date_desc',
  'date_asc',
  'number_asc',
  'number_desc',
  'status_asc',
  'status_desc',
] as const;
export type AuditTypeFilter = (typeof AUDIT_TYPES)[number];
export type AuditStatusFilter = (typeof AUDIT_STATUSES)[number];
export type AuditSort = (typeof AUDIT_SORTS)[number];
const TYPE_LABELS: Record<AuditListItemDto['type'], string> = {
  performance: 'Performance',
  security: 'Sécurité',
  ux: 'UX',
  seo: 'SEO',
  technical: 'Technique',
  accessibility: 'Accessibilité',
};
const STATUS_LABELS: Record<AuditListItemDto['status'], string> = {
  new: 'Non commencé',
  in_progress: 'En cours',
  review: 'En revue',
  done: 'Finalisé',
};
export const typeLabel = (value: string) => TYPE_LABELS[value as AuditListItemDto['type']] ?? value;
export const statusLabel = (value: string) =>
  STATUS_LABELS[value as AuditListItemDto['status']] ?? value;
export const isAuditTypeFilter = (value: string): value is AuditTypeFilter =>
  AUDIT_TYPES.includes(value as AuditTypeFilter);
export const isAuditStatusFilter = (value: string): value is AuditStatusFilter =>
  AUDIT_STATUSES.includes(value as AuditStatusFilter);
export const isAuditSort = (value: string): value is AuditSort =>
  AUDIT_SORTS.includes(value as AuditSort);

export const useAdminAuditsList = () => {
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const timer = useRef<number | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<AuditStatusFilter>('all');
  const [filterType, setFilterType] = useState<AuditTypeFilter>('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [sort, setSort] = useState<AuditSort>('date_desc');
  const load = () => {
    setLoading(true);
    setError(null);
    void adminFetchAudits()
      .then(setItems)
      .catch((e) => setError(e instanceof Error ? e.message : 'Impossible de charger les audits.'))
      .finally(() => setLoading(false));
  };
  useEffect(() => {
    load();
    timer.current = window.setInterval(() => {
      if (!document.hidden)
        void adminFetchAudits()
          .then(setItems)
          .catch(() => undefined);
    }, 15000);
    return () => {
      if (timer.current) window.clearInterval(timer.current);
    };
  }, []);
  const view = useMemo(() => {
    const q = search.trim().toLowerCase();
    const from = fromDate ? new Date(fromDate).getTime() : null;
    const to = toDate ? new Date(toDate).getTime() : null;
    const filtered = items.filter((item) => {
      const created = item.createdAt ? new Date(item.createdAt).getTime() : null;
      return (
        (!q || item.number.toLowerCase().includes(q) || item.url.toLowerCase().includes(q)) &&
        (filterStatus === 'all' || item.status === filterStatus) &&
        (filterType === 'all' || item.type === filterType) &&
        (from === null || (created !== null && created >= from)) &&
        (to === null || (created !== null && created <= to))
      );
    });
    return filtered.sort((left, right) =>
      sort.startsWith('date')
        ? (sort === 'date_desc' ? -1 : 1) *
          (new Date(left.createdAt).getTime() - new Date(right.createdAt).getTime())
        : sort.startsWith('number')
          ? (sort === 'number_desc' ? -1 : 1) * left.number.localeCompare(right.number, 'fr')
          : (sort === 'status_desc' ? -1 : 1) *
            ({ new: 0, in_progress: 1, review: 2, done: 3 }[left.status] -
              { new: 0, in_progress: 1, review: 2, done: 3 }[right.status]),
    );
  }, [items, search, filterStatus, filterType, fromDate, toDate, sort]);
  return {
    loading,
    error,
    search,
    setSearch,
    filterStatus,
    setFilterStatus,
    filterType,
    setFilterType,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    sort,
    setSort,
    view,
  };
};
