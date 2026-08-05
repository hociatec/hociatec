import { useCallback } from 'react';
import { useQuery } from '@tanstack/react-query';

import {
  fetchEmailLogs,
  fetchFulfillmentOrders,
  fetchOperationsOverview,
  fetchRefunds,
  fetchStockMovements,
  fetchSupportRequests,
} from '@/features/admin/operations/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminOperationsQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminOperationsData = () => {
  const operationsQuery = useQuery({
    queryKey: adminOperationsQueryKeys.overview(),
    queryFn: async () => {
      const [overviewData, supportData, refundData, stockData, emailData, fulfillmentData] =
        await Promise.all([
          fetchOperationsOverview(),
          fetchSupportRequests(),
          fetchRefunds(),
          fetchStockMovements(),
          fetchEmailLogs(),
          fetchFulfillmentOrders(),
        ]);
      return {
        overview: overviewData,
        support: supportData,
        refunds: refundData,
        stock: stockData,
        emails: emailData,
        fulfillmentOrders: fulfillmentData,
      };
    },
  });

  const refresh = useCallback(async () => {
    await operationsQuery.refetch();
  }, [operationsQuery]);
  const status: 'loading' | 'error' | 'success' = operationsQuery.isLoading
    ? 'loading'
    : operationsQuery.error
      ? 'error'
      : 'success';

  return {
    overview: operationsQuery.data?.overview ?? null,
    support: operationsQuery.data?.support ?? [],
    refunds: operationsQuery.data?.refunds ?? [],
    stock: operationsQuery.data?.stock ?? [],
    emails: operationsQuery.data?.emails ?? [],
    fulfillmentOrders: operationsQuery.data?.fulfillmentOrders ?? [],
    status,
    message: operationsQuery.error
      ? getHttpErrorMessage(operationsQuery.error, 'Erreur de chargement.')
      : null,
    refresh,
  };
};
