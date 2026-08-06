import { useCallback, useState } from 'react';
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
import type { PaginationMeta } from '@/shared/types/api';

const emptyMeta = (page: number): PaginationMeta => ({ page, perPage: 10, total: 0, totalPages: 1 });

export const useAdminOperationsData = () => {
  const [supportPage, setSupportPage] = useState(1);
  const [refundsPage, setRefundsPage] = useState(1);
  const [stockPage, setStockPage] = useState(1);
  const [emailsPage, setEmailsPage] = useState(1);
  const [fulfillmentPage, setFulfillmentPage] = useState(1);
  const operationsQuery = useQuery({
    queryKey: [
      ...adminOperationsQueryKeys.overview(),
      { emailsPage, fulfillmentPage, refundsPage, stockPage, supportPage },
    ],
    queryFn: async () => {
      const [overviewData, supportData, refundData, stockData, emailData, fulfillmentData] =
        await Promise.all([
          fetchOperationsOverview(),
          fetchSupportRequests(supportPage, 10),
          fetchRefunds(refundsPage, 10),
          fetchStockMovements(stockPage, 10),
          fetchEmailLogs(emailsPage, 10),
          fetchFulfillmentOrders(fulfillmentPage, 10),
        ]);
      return {
        overview: overviewData,
        support: supportData.items,
        supportMeta: supportData.meta,
        refunds: refundData.items,
        refundsMeta: refundData.meta,
        stock: stockData.items,
        stockMeta: stockData.meta,
        emails: emailData.items,
        emailsMeta: emailData.meta,
        fulfillmentOrders: fulfillmentData.items,
        fulfillmentMeta: fulfillmentData.meta,
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
    supportMeta: operationsQuery.data?.supportMeta ?? emptyMeta(supportPage),
    setSupportPage,
    refunds: operationsQuery.data?.refunds ?? [],
    refundsMeta: operationsQuery.data?.refundsMeta ?? emptyMeta(refundsPage),
    setRefundsPage,
    stock: operationsQuery.data?.stock ?? [],
    stockMeta: operationsQuery.data?.stockMeta ?? emptyMeta(stockPage),
    setStockPage,
    emails: operationsQuery.data?.emails ?? [],
    emailsMeta: operationsQuery.data?.emailsMeta ?? emptyMeta(emailsPage),
    setEmailsPage,
    fulfillmentOrders: operationsQuery.data?.fulfillmentOrders ?? [],
    fulfillmentMeta: operationsQuery.data?.fulfillmentMeta ?? emptyMeta(fulfillmentPage),
    setFulfillmentPage,
    status,
    message: operationsQuery.error
      ? getHttpErrorMessage(operationsQuery.error, 'Erreur de chargement.')
      : null,
    refresh,
  };
};
