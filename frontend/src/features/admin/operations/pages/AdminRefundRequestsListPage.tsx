import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsRecentSection } from '@/features/admin/operations/components/OperationsRecentSection';

export const AdminRefundRequestsListPage = () => {
  const operations = useAdminOperations({
    refunds: true,
    overview: false,
    support: false,
    stock: false,
    emails: false,
    fulfillment: false,
  });

  return (
    <PageContainer size="admin" title="Demandes de remboursement">
      <OperationsHeader
        description="Consultation, validation et traitement des remboursements en attente."
        message={operations.message}
        status={operations.status}
      />
      <OperationsRecentSection
        mode="refunds"
        emails={[]}
        emailsMeta={operations.emailsMeta}
        refundConfirmations={operations.refundConfirmations}
        refunds={operations.refunds}
        refundsMeta={operations.refundsMeta}
        setEmailsPage={operations.setEmailsPage}
        setRefundConfirmations={operations.setRefundConfirmations}
        setRefundsPage={operations.setRefundsPage}
        setStockPage={operations.setStockPage}
        setStockThresholds={operations.setStockThresholds}
        stock={[]}
        stockMeta={operations.stockMeta}
        stockThresholds={operations.stockThresholds}
        submitStockThreshold={operations.submitStockThreshold}
        submitStripeRefund={operations.submitStripeRefund}
        support={[]}
        supportMeta={operations.supportMeta}
        setSupportPage={operations.setSupportPage}
        updateRefundStatus={operations.updateRefundStatus}
      />
    </PageContainer>
  );
};
