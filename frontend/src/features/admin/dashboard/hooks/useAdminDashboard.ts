import { useEffect, useState } from 'react';

import { fetchAdminDashboard, type AdminDashboardDto } from '@/features/admin/customers/api';

export const useAdminDashboard = () => {
  const [dashboard, setDashboard] = useState<AdminDashboardDto | null>(null);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void fetchAdminDashboard()
      .then((data) => { setDashboard(data); setStatus('success'); })
      .catch((reason: unknown) => { setStatus('error'); setError(reason instanceof Error ? reason.message : "Les indicateurs d'administration n'ont pas pu être chargés."); });
  }, []);

  return { dashboard, error, status };
};
