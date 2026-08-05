import { useQuery } from '@tanstack/react-query';

import { fetchMyVouchers, type MyVoucherDto } from '../api/vouchersApi';
import { voucherQueryKeys } from '@/shared/lib/queryKeys';

export const useVouchers = () => {
  const query = useQuery<MyVoucherDto[], Error>({
    queryKey: voucherQueryKeys.mine(),
    queryFn: fetchMyVouchers,
  });

  return {
    vouchers: query.data ?? [],
    loading: query.isLoading,
    error: query.error?.message ?? null,
  };
};
