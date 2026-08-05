import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  convertMyLoyalty,
  emptyLoyalty,
  fetchDashboardData,
} from '@/features/account/api/dashboardApi';
import {
  getDefaultConvertPoints,
  normalizeConversionPoints,
  selectDashboardActions,
} from '@/features/account/lib/dashboardSelectors';
import type {
  DashboardConversionState,
  DashboardData,
  DashboardLoadState,
} from '@/features/account/types/dashboard';
import { useToast } from '@/shared/components/ui/toast';
import { formatOptionalEuroCents } from '@/shared/lib/formatters';
import { accountQueryKeys } from '@/shared/lib/queryKeys';

export const useClientDashboard = () => {
  const toast = useToast();
  const queryClient = useQueryClient();
  const [convertPoints, setConvertPoints] = useState('100');
  const dashboardQuery = useQuery({
    queryKey: accountQueryKeys.dashboard(),
    queryFn: fetchDashboardData,
  });
  const data: DashboardData = dashboardQuery.data?.data ?? {
    quotes: [],
    appointments: [],
    trainings: [],
    pendingReviews: [],
    loyalty: emptyLoyalty,
  };
  const state: DashboardLoadState = dashboardQuery.isLoading
    ? 'loading'
    : dashboardQuery.data?.hasError
      ? 'error'
      : 'success';
  const loadedAtMs = dashboardQuery.dataUpdatedAt;

  useEffect(() => {
    if (data.loyalty.points <= 0) {
      setConvertPoints('0');
      return;
    }
    const currentPoints = Number.parseInt(convertPoints, 10) || 0;
    if (currentPoints <= 0 || currentPoints > data.loyalty.points)
      setConvertPoints(getDefaultConvertPoints(data.loyalty.points));
  }, [convertPoints, data.loyalty.points]);

  const dashboardActions = useMemo(
    () => selectDashboardActions(data, loadedAtMs),
    [data, loadedAtMs],
  );
  const conversionPoints = normalizeConversionPoints(convertPoints);
  const conversionEuroCents =
    Math.floor(conversionPoints / data.loyalty.pointsPerEuroConverted) * 100;
  const convertMutation = useMutation({
    mutationFn: convertMyLoyalty,
    onSuccess: (result) => {
      queryClient.setQueryData<Awaited<ReturnType<typeof fetchDashboardData>>>(
        accountQueryKeys.dashboard(),
        (current) => ({
          data: {
            ...(current?.data ?? data),
            loyalty: result.loyalty,
          },
          hasError: current?.hasError ?? false,
        }),
      );
      setConvertPoints('100');
      toast.show(
        `Bon ${result.voucher.code} créé pour ${formatOptionalEuroCents(result.voucher.discountValue)}.`,
        { variant: 'success' },
      );
    },
    onError: (error) =>
      toast.show(error instanceof Error ? error.message : 'Impossible de convertir vos points.', {
        variant: 'error',
      }),
  });

  const handleConvert = () => {
    convertMutation.mutate(conversionPoints);
  };
  const conversionState: DashboardConversionState = convertMutation.isPending ? 'saving' : 'idle';

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
