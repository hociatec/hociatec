import { useAdminOperationsActions } from './useAdminOperationsActions';
import { useAdminOperationsData } from './useAdminOperationsData';

export const useAdminOperations = () => {
  const data = useAdminOperationsData();
  const actions = useAdminOperationsActions(data.refresh);
  const failedEmails = data.overview?.emails.failedCount ?? 0;
  const hasPriorities = Boolean(
    (data.overview?.support.openCount ?? 0) > 0 ||
      (data.overview?.refunds.pendingCount ?? 0) > 0 ||
      (data.overview?.stock.lowStockCount ?? 0) > 0 ||
      failedEmails > 0,
  );

  return {
    ...data,
    ...actions,
    message: actions.message ?? data.message,
    failedEmails,
    hasPriorities,
  };
};
