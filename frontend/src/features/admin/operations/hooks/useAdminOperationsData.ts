import { useCallback, useEffect, useState } from 'react';

import {
  fetchEmailLogs,
  fetchFulfillmentOrders,
  fetchOperationsOverview,
  fetchRefunds,
  fetchStockMovements,
  fetchSupportRequests,
  type EmailLogDto,
  type FulfillmentOrderDto,
  type OperationsOverviewDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const useAdminOperationsData = () => {
  const [overview, setOverview] = useState<OperationsOverviewDto | null>(null);
  const [support, setSupport] = useState<SupportRequestDto[]>([]);
  const [refunds, setRefunds] = useState<RefundRequestDto[]>([]);
  const [stock, setStock] = useState<StockMovementDto[]>([]);
  const [emails, setEmails] = useState<EmailLogDto[]>([]);
  const [fulfillmentOrders, setFulfillmentOrders] = useState<FulfillmentOrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [message, setMessage] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setStatus('loading');
    setMessage(null);
    try {
      const [overviewData, supportData, refundData, stockData, emailData, fulfillmentData] =
        await Promise.all([
          fetchOperationsOverview(),
          fetchSupportRequests(),
          fetchRefunds(),
          fetchStockMovements(),
          fetchEmailLogs(),
          fetchFulfillmentOrders(),
        ]);
      setOverview(overviewData);
      setSupport(supportData);
      setRefunds(refundData);
      setStock(stockData);
      setEmails(emailData);
      setFulfillmentOrders(fulfillmentData);
      setStatus('success');
    } catch (error) {
      setMessage(getHttpErrorMessage(error, 'Erreur de chargement.'));
      setStatus('error');
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return {
    overview,
    support,
    refunds,
    stock,
    emails,
    fulfillmentOrders,
    status,
    message,
    refresh,
  };
};
