import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/publicApi';
import { auditQueryKeys } from '@/features/audits/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

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

export const useAdminAuditsList = () => {
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<AuditStatusFilter>('all');
  const [filterType, setFilterType] = useState<AuditTypeFilter>('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [sort, setSort] = useState<AuditSort>('date_desc');
  const [page, setPage] = useState(1);
  const auditsQuery = useQuery<PaginatedResult<AuditListItemDto>, Error>({
    queryKey: [
      ...auditQueryKeys.adminList(),
      { fromDate, filterStatus, filterType, page, search, sort, toDate },
    ],
    queryFn: () =>
      adminFetchAudits(page, 10, {
        from: fromDate,
        q: search,
        sort,
        status: filterStatus,
        to: toDate,
        type: filterType,
      }),
    refetchInterval: (currentQuery) => {
      if (document.hidden || currentQuery.state.error) {
        return false;
      }

      const items = currentQuery.state.data?.items ?? [];

      return items.some((item) => item.status !== 'done') ? 15_000 : false;
    },
  });
  const items = auditsQuery.data?.items ?? [];
  const pagination = auditsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };

  useEffect(() => {
    setPage(1);
  }, [search, filterStatus, filterType, fromDate, toDate, sort]);

  useEffect(() => {
    if (page > pagination.totalPages) {
      setPage(pagination.totalPages);
    }
  }, [page, pagination.totalPages]);

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
    view: items,
    paginatedView: items,
    page: pagination.page,
    setPage,
    total: pagination.total,
    totalPages: pagination.totalPages,
  };
};
