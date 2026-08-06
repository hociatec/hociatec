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
import { accountQueryKeys } from '@/features/account/queryKeys';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNonNegativeInteger } from '@/shared/lib/parsers';

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
  const errorMessage =
    dashboardQuery.error
      ? getHttpErrorMessage(dashboardQuery.error, 'Impossible de charger votre espace client.')
      : dashboardQuery.data?.hasError
        ? 'Certaines informations n’ont pas pu être chargées. Les accès rapides restent disponibles.'
        : null;
  const state: DashboardLoadState = dashboardQuery.isLoading
    ? 'loading'
    : errorMessage
      ? 'error'
      : 'success';
  const loadedAtMs = dashboardQuery.dataUpdatedAt;

  useEffect(() => {
    if (data.loyalty.points <= 0) {
      setConvertPoints('0');
      return;
    }
    const currentPoints = parseNonNegativeInteger(convertPoints, 0);
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
    error: errorMessage,
    dashboardActions,
    hasError: Boolean(errorMessage),
    hasConvertiblePoints: data.loyalty.points > 0,
    loading: dashboardQuery.isLoading,
    loyalty: data.loyalty,
    setConvertPoints,
    state,
    refresh: dashboardQuery.refetch,
    handleConvert,
  };
};
