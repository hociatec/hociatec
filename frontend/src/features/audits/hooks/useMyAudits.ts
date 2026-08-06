import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { fetchMyAudits, type AuditListItemDto } from '../api/auditsApi';
import { auditQueryKeys } from '@/features/audits/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';
import { shouldRefetchWhenVisible } from '@/shared/lib/browserVisibility';

export const useMyAudits = () => {
  const [page, setPage] = useState(1);
  const query = useQuery<PaginatedResult<AuditListItemDto>, Error>({
    queryKey: [...auditQueryKeys.mine(), { page }],
    queryFn: () => fetchMyAudits(page, 10),
    refetchInterval: (currentQuery) => {
      if (!shouldRefetchWhenVisible(!!currentQuery.state.error)) {
        return false;
      }

      const items = currentQuery.state.data?.items ?? [];

      return items.some((item) => item.status !== 'done') ? 15_000 : false;
    },
    refetchIntervalInBackground: false,
  });

  return {
    items: query.data?.items ?? [],
    pagination: query.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    loading: query.isLoading,
    error: query.error?.message ?? null,
    retry: query.refetch,
  };
};
