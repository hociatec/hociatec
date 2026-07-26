import { useEffect, useState } from 'react';

import { fetchAdminPayments, type AdminPaymentDto } from '@/features/orders/api';

export type AdminPaymentStatus = 'all' | 'open' | 'paid' | 'expired' | 'failed';

export const useAdminPaymentsList = () => {
  const [items, setItems] = useState<AdminPaymentDto[]>([]);
  const [status, setStatus] = useState<AdminPaymentStatus>('all');
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchAdminPayments(status, search)
      .then(setItems)
      .catch((reason: unknown) =>
        setError(reason instanceof Error ? reason.message : 'Impossible de charger les paiements.'),
      )
      .finally(() => setLoading(false));
  }, [search, status]);

  return { items, status, setStatus, search, setSearch, loading, error };
};
