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

export type AdminOperationsDataScope = Partial<{
  overview: boolean;
  support: boolean;
  refunds: boolean;
  stock: boolean;
  emails: boolean;
  fulfillment: boolean;
}>;

const defaultScope: Required<AdminOperationsDataScope> = {
  overview: true,
  support: true,
  refunds: true,
  stock: true,
  emails: true,
  fulfillment: true,
};

export const useAdminOperationsData = (scope: AdminOperationsDataScope = {}) => {
  const enabledScope = { ...defaultScope, ...scope };
  const [supportPage, setSupportPage] = useState(1);
  const [refundsPage, setRefundsPage] = useState(1);
  const [stockPage, setStockPage] = useState(1);
  const [emailsPage, setEmailsPage] = useState(1);
  const [fulfillmentPage, setFulfillmentPage] = useState(1);
  const overviewQuery = useQuery({
    queryKey: adminOperationsQueryKeys.overview(),
    queryFn: fetchOperationsOverview,
    enabled: enabledScope.overview,
  });
  const supportQuery = useQuery({
    queryKey: adminOperationsQueryKeys.support(supportPage),
    queryFn: () => fetchSupportRequests(supportPage, 10),
    enabled: enabledScope.support,
  });
  const refundsQuery = useQuery({
    queryKey: adminOperationsQueryKeys.refunds(refundsPage),
    queryFn: () => fetchRefunds(refundsPage, 10),
    enabled: enabledScope.refunds,
  });
  const stockQuery = useQuery({
    queryKey: adminOperationsQueryKeys.stock(stockPage),
    queryFn: () => fetchStockMovements(stockPage, 10),
    enabled: enabledScope.stock,
  });
  const emailsQuery = useQuery({
    queryKey: adminOperationsQueryKeys.emails(emailsPage),
    queryFn: () => fetchEmailLogs(emailsPage, 10),
    enabled: enabledScope.emails,
  });
  const fulfillmentQuery = useQuery({
    queryKey: adminOperationsQueryKeys.fulfillment(fulfillmentPage),
    queryFn: () => fetchFulfillmentOrders(fulfillmentPage, 10),
    enabled: enabledScope.fulfillment,
  });

  const refresh = useCallback(async () => {
    await Promise.all([
      enabledScope.overview ? overviewQuery.refetch() : Promise.resolve(),
      enabledScope.support ? supportQuery.refetch() : Promise.resolve(),
      enabledScope.refunds ? refundsQuery.refetch() : Promise.resolve(),
      enabledScope.stock ? stockQuery.refetch() : Promise.resolve(),
      enabledScope.emails ? emailsQuery.refetch() : Promise.resolve(),
      enabledScope.fulfillment ? fulfillmentQuery.refetch() : Promise.resolve(),
    ]);
  }, [emailsQuery, enabledScope, fulfillmentQuery, overviewQuery, refundsQuery, stockQuery, supportQuery]);

  const activeQueries = [
    enabledScope.overview ? overviewQuery : null,
    enabledScope.support ? supportQuery : null,
    enabledScope.refunds ? refundsQuery : null,
    enabledScope.stock ? stockQuery : null,
    enabledScope.emails ? emailsQuery : null,
    enabledScope.fulfillment ? fulfillmentQuery : null,
  ].filter((query): query is NonNullable<typeof query> => query !== null);

  const status: 'loading' | 'error' | 'success' =
    activeQueries.some((query) => query.isLoading)
      ? 'loading'
      : activeQueries.some((query) => query.error)
        ? 'error'
        : 'success';

  const firstError =
    (enabledScope.overview ? overviewQuery.error : null) ??
    (enabledScope.support ? supportQuery.error : null) ??
    (enabledScope.refunds ? refundsQuery.error : null) ??
    (enabledScope.stock ? stockQuery.error : null) ??
    (enabledScope.emails ? emailsQuery.error : null) ??
    (enabledScope.fulfillment ? fulfillmentQuery.error : null);

  return {
    overview: enabledScope.overview ? overviewQuery.data ?? null : null,
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
    message: firstError ? getHttpErrorMessage(firstError, 'Erreur de chargement.') : null,
    refresh,
  };
};
