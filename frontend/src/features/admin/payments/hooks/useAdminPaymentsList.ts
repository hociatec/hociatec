import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchAdminPaymentMetadata, fetchAdminPayments, type AdminPaymentDto } from '@/features/orders/publicApi';
import type { OrderStatusOptionDto } from '@/features/orders/publicApi';
import { adminPaymentQueryKeys } from '@/shared/lib/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

export type AdminPaymentStatus = 'all' | 'open' | 'paid' | 'expired' | 'failed';

export const useAdminPaymentsList = () => {
  const [status, setStatus] = useState<AdminPaymentStatus>('all');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const metadataQuery = useQuery<{ statuses: OrderStatusOptionDto[] }, Error>({
    queryKey: adminPaymentQueryKeys.metadata(),
    queryFn: fetchAdminPaymentMetadata,
  });
  const paymentsQuery = useQuery<PaginatedResult<AdminPaymentDto>, Error>({
    queryKey: [...adminPaymentQueryKeys.list(status, search), { page }],
    queryFn: () => fetchAdminPayments(status, search, page, 10),
  });
  useEffect(() => {
    setPage(1);
  }, [search, status]);

  return {
    items: paymentsQuery.data?.items ?? [],
    pagination: paymentsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
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
