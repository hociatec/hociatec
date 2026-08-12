import { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import { fetchAdminPaymentMetadata, fetchAdminPayments, type AdminPaymentDto } from '@/features/orders/publicApi';
import type { OrderStatusOptionDto } from '@/features/orders/publicApi';
import { adminPaymentQueryKeys } from '@/features/admin/payments/queryKeys';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import type { PaginatedResult } from '@/shared/types/api';

export type AdminPaymentStatus = 'all' | 'open' | 'paid' | 'expired' | 'failed';

export const useAdminPaymentsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [status, setStatus] = useState<AdminPaymentStatus>(
    (searchParams.get('status') as AdminPaymentStatus | null) ?? 'all',
  );
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const debouncedSearch = useDebounce(search.trim(), 250);
  const metadataQuery = useQuery<{ statuses: OrderStatusOptionDto[] }, Error>({
    queryKey: adminPaymentQueryKeys.metadata(),
    queryFn: fetchAdminPaymentMetadata,
  });
  const paymentsQuery = useQuery<PaginatedResult<AdminPaymentDto>, Error>({
    queryKey: [...adminPaymentQueryKeys.list(status, debouncedSearch), { page }],
    queryFn: () => fetchAdminPayments(status, debouncedSearch, page, 10),
  });

  useEffect(() => {
    setPage(1);
  }, [debouncedSearch, status]);

  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim()) {
      next.set('q', search.trim());
    }
    if (status !== 'all') {
      next.set('status', status);
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, search, setSearchParams, status]);

  const pagination = useMemo(
    () => paymentsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    [page, paymentsQuery.data?.meta],
  );

  return {
    items: paymentsQuery.data?.items ?? [],
    pagination,
    setPage,
    status,
    setStatus,
    statusOptions: metadataQuery.data?.statuses ?? [],
    search,
    setSearch,
    loading: paymentsQuery.isLoading,
    error: paymentsQuery.error?.message ?? null,
  };
};
