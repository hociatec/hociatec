import { useCallback, useEffect, useState } from 'react';
import { fetchMyAudits, type AuditListItemDto } from '../api/auditsApi';

export const useMyAudits = () => {
  const [items, setItems] = useState<AuditListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const refresh = useCallback((silent = false) => {
    if (!silent) { setLoading(true); setError(null); }
    return fetchMyAudits().then(setItems).catch((reason: Error) => { if (!silent) setError(reason.message); }).finally(() => { if (!silent) setLoading(false); });
  }, []);
  useEffect(() => { void refresh(); }, [refresh]);
  useEffect(() => {
    const timer = window.setInterval(() => { if (!document.hidden) void refresh(true); }, 15000);
    return () => window.clearInterval(timer);
  }, [refresh]);
  return { items, loading, error };
};
