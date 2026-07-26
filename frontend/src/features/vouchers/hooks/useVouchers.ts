import { useEffect, useState } from 'react';
import { fetchMyVouchers, type MyVoucherDto } from '../api/vouchersApi';

export const useVouchers = () => {
  const [vouchers, setVouchers] = useState<MyVoucherDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => { setLoading(true); void fetchMyVouchers().then(setVouchers).catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Impossible de charger vos bons de réduction.')).finally(() => setLoading(false)); }, []);
  return { vouchers, loading, error };
};
