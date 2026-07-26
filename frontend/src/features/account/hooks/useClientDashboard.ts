import { useEffect, useMemo, useState } from 'react';

import { convertMyLoyalty, emptyLoyalty, fetchDashboardData } from '@/features/account/api/dashboardApi';
import { getDefaultConvertPoints, normalizeConversionPoints, selectDashboardActions } from '@/features/account/lib/dashboardSelectors';
import type { DashboardConversionState, DashboardData, DashboardLoadState } from '@/features/account/types/dashboard';
import { useToast } from '@/shared/components/ui/toast';
import { formatOptionalEuroCents } from '@/shared/lib/formatters';

export const useClientDashboard = () => {
  const toast = useToast();
  const [data, setData] = useState<DashboardData>({ quotes: [], appointments: [], trainings: [], pendingReviews: [], loyalty: emptyLoyalty });
  const [convertPoints, setConvertPoints] = useState('100');
  const [state, setState] = useState<DashboardLoadState>('loading');
  const [conversionState, setConversionState] = useState<DashboardConversionState>('idle');
  const [loadedAtMs, setLoadedAtMs] = useState(0);

  useEffect(() => {
    let cancelled = false;
    void fetchDashboardData().then((result) => {
      if (cancelled) return;
      setData(result.data);
      setLoadedAtMs(Date.now());
      setState(result.hasError ? 'error' : 'success');
    });
    return () => { cancelled = true; };
  }, []);

  useEffect(() => {
    if (data.loyalty.points <= 0) {
      setConvertPoints('0');
      return;
    }
    const currentPoints = Number.parseInt(convertPoints, 10) || 0;
    if (currentPoints <= 0 || currentPoints > data.loyalty.points) setConvertPoints(getDefaultConvertPoints(data.loyalty.points));
  }, [convertPoints, data.loyalty.points]);

  const dashboardActions = useMemo(() => selectDashboardActions(data, loadedAtMs), [data, loadedAtMs]);
  const conversionPoints = normalizeConversionPoints(convertPoints);
  const conversionEuroCents = Math.floor(conversionPoints / data.loyalty.pointsPerEuroConverted) * 100;

  const handleConvert = () => {
    setConversionState('saving');
    void convertMyLoyalty(conversionPoints)
      .then((result) => {
        setData((current) => ({ ...current, loyalty: result.loyalty }));
        setConvertPoints('100');
        toast.show(`Bon ${result.voucher.code} créé pour ${formatOptionalEuroCents(result.voucher.discountValue)}.`, { variant: 'success' });
      })
      .catch((error: unknown) => toast.show(error instanceof Error ? error.message : 'Impossible de convertir vos points.', { variant: 'error' }))
      .finally(() => setConversionState('idle'));
  };

  return {
    conversionEuroCents,
    conversionPoints,
    conversionState,
    convertPoints,
    dashboardActions,
    hasConvertiblePoints: data.loyalty.points > 0,
    loyalty: data.loyalty,
    setConvertPoints,
    state,
    handleConvert,
  };
};
