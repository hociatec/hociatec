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
import { adminOperationsQueryKeys } from '@/features/admin/operations/queryKeys';
import type { PaginationMeta } from '@/shared/types/api';

const emptyMeta = (page: number): PaginationMeta => ({ page, perPage: 10, total: 0, totalPages: 1 });

export const useAdminOperationsData = () => {
  const [supportPage, setSupportPage] = useState(1);
  const [refundsPage, setRefundsPage] = useState(1);
  const [stockPage, setStockPage] = useState(1);
  const [emailsPage, setEmailsPage] = useState(1);
  const [fulfillmentPage, setFulfillmentPage] = useState(1);
  const overviewQuery = useQuery({
    queryKey: adminOperationsQueryKeys.overview(),
    queryFn: fetchOperationsOverview,
  });
  const supportQuery = useQuery({
    queryKey: adminOperationsQueryKeys.support(supportPage),
    queryFn: () => fetchSupportRequests(supportPage, 10),
  });
  const refundsQuery = useQuery({
    queryKey: adminOperationsQueryKeys.refunds(refundsPage),
    queryFn: () => fetchRefunds(refundsPage, 10),
  });
  const stockQuery = useQuery({
    queryKey: adminOperationsQueryKeys.stock(stockPage),
    queryFn: () => fetchStockMovements(stockPage, 10),
  });
  const emailsQuery = useQuery({
    queryKey: adminOperationsQueryKeys.emails(emailsPage),
    queryFn: () => fetchEmailLogs(emailsPage, 10),
  });
  const fulfillmentQuery = useQuery({
    queryKey: adminOperationsQueryKeys.fulfillment(fulfillmentPage),
    queryFn: () => fetchFulfillmentOrders(fulfillmentPage, 10),
  });

  const refresh = useCallback(async () => {
    await Promise.all([
      overviewQuery.refetch(),
      supportQuery.refetch(),
      refundsQuery.refetch(),
      stockQuery.refetch(),
      emailsQuery.refetch(),
      fulfillmentQuery.refetch(),
    ]);
  }, [emailsQuery, fulfillmentQuery, overviewQuery, refundsQuery, stockQuery, supportQuery]);
  const status: 'loading' | 'error' | 'success' =
    overviewQuery.isLoading ||
    supportQuery.isLoading ||
    refundsQuery.isLoading ||
    stockQuery.isLoading ||
    emailsQuery.isLoading ||
    fulfillmentQuery.isLoading
    ? 'loading'
    : overviewQuery.error ||
        supportQuery.error ||
        refundsQuery.error ||
        stockQuery.error ||
        emailsQuery.error ||
        fulfillmentQuery.error
      ? 'error'
      : 'success';

  return {
    overview: overviewQuery.data ?? null,
    support: supportQuery.data?.items ?? [],
    supportMeta: supportQuery.data?.meta ?? emptyMeta(supportPage),
    setSupportPage,
    refunds: refundsQuery.data?.items ?? [],
    refundsMeta: refundsQuery.data?.meta ?? emptyMeta(refundsPage),
    setRefundsPage,
    stock: stockQuery.data?.items ?? [],
    stockMeta: stockQuery.data?.meta ?? emptyMeta(stockPage),
    setStockPage,
    emails: emailsQuery.data?.items ?? [],
    emailsMeta: emailsQuery.data?.meta ?? emptyMeta(emailsPage),
    setEmailsPage,
    fulfillmentOrders: fulfillmentQuery.data?.items ?? [],
    fulfillmentMeta: fulfillmentQuery.data?.meta ?? emptyMeta(fulfillmentPage),
    setFulfillmentPage,
    status,
    message:
      overviewQuery.error
        ? getHttpErrorMessage(overviewQuery.error, 'Erreur de chargement.')
        : supportQuery.error
          ? getHttpErrorMessage(supportQuery.error, 'Erreur de chargement.')
          : refundsQuery.error
            ? getHttpErrorMessage(refundsQuery.error, 'Erreur de chargement.')
            : stockQuery.error
              ? getHttpErrorMessage(stockQuery.error, 'Erreur de chargement.')
              : emailsQuery.error
                ? getHttpErrorMessage(emailsQuery.error, 'Erreur de chargement.')
                : fulfillmentQuery.error
                  ? getHttpErrorMessage(fulfillmentQuery.error, 'Erreur de chargement.')
                  : null,
    refresh,
  };
};
