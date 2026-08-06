import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';

import { fetchMyVouchers, type MyVoucherDto } from '../api/vouchersApi';
import { voucherQueryKeys } from '@/features/vouchers/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

export const useVouchers = () => {
  const [page, setPage] = useState(1);
  const query = useQuery<PaginatedResult<MyVoucherDto>, Error>({
    queryKey: [...voucherQueryKeys.mine(), { page }],
    queryFn: () => fetchMyVouchers(page, 10),
  });

  return {
    vouchers: query.data?.items ?? [],
    pagination: query.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    loading: query.isLoading,
    error: query.error?.message ?? null,
  };
};
