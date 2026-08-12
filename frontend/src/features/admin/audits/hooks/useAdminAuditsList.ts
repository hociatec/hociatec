import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { adminFetchAudits, type AuditListItemDto } from '@/features/audits/publicApi';
import { auditQueryKeys } from '@/features/audits/publicApi';
import type { PaginatedResult } from '@/shared/types/api';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { shouldRefetchWhenVisible } from '@/shared/lib/browserVisibility';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

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
  const [searchParams, setSearchParams] = useSearchParams();
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [filterStatus, setFilterStatus] = useState<AuditStatusFilter>(
    (searchParams.get('status') as AuditStatusFilter | null) ?? 'all',
  );
  const [filterType, setFilterType] = useState<AuditTypeFilter>(
    (searchParams.get('type') as AuditTypeFilter | null) ?? 'all',
  );
  const [fromDate, setFromDate] = useState(searchParams.get('from'));
  const [toDate, setToDate] = useState(searchParams.get('to'));
  const [sort, setSort] = useState<AuditSort>(
    (searchParams.get('sort') as AuditSort | null) ?? 'date_desc',
  );
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const debouncedSearch = useDebounce(search.trim(), 250);
  const debouncedFilterStatus = useDebounce(filterStatus, 150);
  const debouncedFilterType = useDebounce(filterType, 150);
  const auditsQuery = useQuery<PaginatedResult<AuditListItemDto>, Error>({
    queryKey: [
      ...auditQueryKeys.adminList(),
      {
        fromDate,
        filterStatus: debouncedFilterStatus,
        filterType: debouncedFilterType,
        page,
        search: debouncedSearch,
        sort,
        toDate,
      },
    ],
    queryFn: () =>
      adminFetchAudits(page, 10, {
        from: fromDate,
        q: debouncedSearch,
        sort,
        status: debouncedFilterStatus,
        to: toDate,
        type: debouncedFilterType,
      }),
    refetchInterval: (currentQuery) => {
      if (
        !shouldRefetchWhenVisible(!!currentQuery.state.error)
      ) {
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
  }, [debouncedSearch, debouncedFilterStatus, debouncedFilterType, fromDate, toDate, sort]);

  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim()) {
      next.set('q', search.trim());
    }
    if (filterStatus !== 'all') {
      next.set('status', filterStatus);
    }
    if (filterType !== 'all') {
      next.set('type', filterType);
    }
    if (fromDate) {
      next.set('from', fromDate);
    }
    if (toDate) {
      next.set('to', toDate);
    }
    if (sort !== 'date_desc') {
      next.set('sort', sort);
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [filterStatus, filterType, fromDate, page, search, setSearchParams, sort, toDate]);

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
