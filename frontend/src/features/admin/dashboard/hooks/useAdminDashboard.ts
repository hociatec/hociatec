import { useQuery } from '@tanstack/react-query';

import { fetchAdminDashboard, type AdminDashboardDto } from '@/features/admin/customers/api';
import { adminDashboardQueryKeys } from '@/features/admin/dashboard/queryKeys';

type AdminDashboardStatus = 'loading' | 'error' | 'success';

export const useAdminDashboard = () => {
  const query = useQuery<AdminDashboardDto, Error>({
    queryKey: adminDashboardQueryKeys.dashboard(),
    queryFn: fetchAdminDashboard,
  });

  const status: AdminDashboardStatus = query.isLoading ? 'loading' : query.isError ? 'error' : 'success';

  return {
    dashboard: query.data ?? null,
    error:
      query.error?.message ??
      (query.isError ? "Les indicateurs d'administration n'ont pas pu être chargés." : null),
    status,
  };
};
