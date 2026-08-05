import { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/publicApi';
import { auditQueryKeys } from '@/shared/lib/queryKeys';

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
export const isAuditTypeFilter = (value: string): value is AuditTypeFilter =>
  AUDIT_TYPES.includes(value as AuditTypeFilter);
export const isAuditStatusFilter = (value: string): value is AuditStatusFilter =>
  AUDIT_STATUSES.includes(value as AuditStatusFilter);
export const isAuditSort = (value: string): value is AuditSort =>
  AUDIT_SORTS.includes(value as AuditSort);

const ADMIN_AUDITS_PER_PAGE = 50;

export const useAdminAuditsList = () => {
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<AuditStatusFilter>('all');
  const [filterType, setFilterType] = useState<AuditTypeFilter>('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [sort, setSort] = useState<AuditSort>('date_desc');
  const [page, setPage] = useState(1);
  const auditsQuery = useQuery<AuditListItemDto[], Error>({
    queryKey: auditQueryKeys.adminList(),
    queryFn: adminFetchAudits,
    refetchInterval: (currentQuery) => {
      if (document.hidden || currentQuery.state.error) {
        return false;
      }

      const items = currentQuery.state.data ?? [];

      return items.some((item) => item.status !== 'done') ? 15_000 : false;
    },
  });
  const items = auditsQuery.data ?? [];
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

  const totalPages = Math.max(1, Math.ceil(view.length / ADMIN_AUDITS_PER_PAGE));
  const currentPage = Math.min(page, totalPages);
  const paginatedView = useMemo(() => {
    const start = (currentPage - 1) * ADMIN_AUDITS_PER_PAGE;

    return view.slice(start, start + ADMIN_AUDITS_PER_PAGE);
  }, [currentPage, view]);

  useEffect(() => {
    setPage(1);
  }, [search, filterStatus, filterType, fromDate, toDate, sort]);

  useEffect(() => {
    if (page > totalPages) {
      setPage(totalPages);
    }
  }, [page, totalPages]);

  return {
    loading: auditsQuery.isLoading,
    error: auditsQuery.error?.message ?? null,
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
    paginatedView,
    page: currentPage,
    setPage,
    totalPages,
  };
};
