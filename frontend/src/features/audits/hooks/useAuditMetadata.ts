import { useEffect, useState } from 'react';

import { fetchAuditMetadata, type AuditMetadataDto } from '../api/auditsApi';

export const useAuditMetadata = () => {
  const [metadata, setMetadata] = useState<AuditMetadataDto>({ types: [], statuses: [] });

  useEffect(() => {
    let cancelled = false;
    void fetchAuditMetadata().then((value) => {
      if (!cancelled) setMetadata(value);
    });
    return () => {
      cancelled = true;
    };
  }, []);

  return metadata;
};
