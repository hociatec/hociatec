import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchAdminPaymentMetadata, fetchAdminPayments, type AdminPaymentDto } from '@/features/orders/api';
import type { OrderStatusOptionDto } from '@/features/orders/orderTypes';
import { adminPaymentQueryKeys } from '@/shared/lib/queryKeys';

export type AdminPaymentStatus = 'all' | 'open' | 'paid' | 'expired' | 'failed';

export const useAdminPaymentsList = () => {
  const [status, setStatus] = useState<AdminPaymentStatus>('all');
  const [search, setSearch] = useState('');
  const metadataQuery = useQuery<{ statuses: OrderStatusOptionDto[] }, Error>({
    queryKey: adminPaymentQueryKeys.metadata(),
    queryFn: fetchAdminPaymentMetadata,
  });
  const paymentsQuery = useQuery<AdminPaymentDto[], Error>({
    queryKey: adminPaymentQueryKeys.list(status, search),
    queryFn: () => fetchAdminPayments(status, search),
  });

  return {
    items: paymentsQuery.data ?? [],
    status,
    setStatus,
    statusOptions: metadataQuery.data?.statuses ?? [],
    search,
    setSearch,
    loading: paymentsQuery.isLoading,
    error: paymentsQuery.error?.message ?? null,
  };
};
