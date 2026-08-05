import { useQuery } from '@tanstack/react-query';

import { fetchAuditMetadata, type AuditMetadataDto } from '../api/auditsApi';
import { auditQueryKeys } from '@/shared/lib/queryKeys';

export const useAuditMetadata = () => {
  const query = useQuery<AuditMetadataDto>({
    queryKey: auditQueryKeys.metadata(),
    queryFn: fetchAuditMetadata,
  });

  return query.data ?? { types: [], statuses: [] };
};
