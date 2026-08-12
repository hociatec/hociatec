import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsRecentSection } from '@/features/admin/operations/components/OperationsRecentSection';

export const AdminSupportRequestsListPage = () => {
  const operations = useAdminOperations({
    support: true,
    overview: false,
    refunds: false,
    stock: false,
    emails: false,
    fulfillment: false,
  });

  return (
    <PageContainer size="admin" title="Demandes SAV">
      <OperationsHeader
        description="Consultation, suivi et réponses aux dossiers SAV ouverts."
        message={operations.message}
        status={operations.status}
      />
      <OperationsRecentSection
        mode="support"
        emails={[]}
        emailsMeta={operations.emailsMeta}
        refundConfirmations={{}}
        refunds={[]}
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
        support={operations.support}
        supportMeta={operations.supportMeta}
        setSupportPage={operations.setSupportPage}
        updateRefundStatus={operations.updateRefundStatus}
      />
    </PageContainer>
  );
};
