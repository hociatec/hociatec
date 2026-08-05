import { useQuery } from '@tanstack/react-query';
import { fetchMyAudits, type AuditListItemDto } from '../api/auditsApi';
import { auditQueryKeys } from '@/shared/lib/queryKeys';

export const useMyAudits = () => {
  const query = useQuery<AuditListItemDto[], Error>({
    queryKey: auditQueryKeys.mine(),
    queryFn: fetchMyAudits,
    refetchInterval: 15_000,
    refetchIntervalInBackground: false,
  });

  return {
    items: query.data ?? [],
    loading: query.isLoading,
    error: query.error?.message ?? null,
    retry: query.refetch,
  };
};
