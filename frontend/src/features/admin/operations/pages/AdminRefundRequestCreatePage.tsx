import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsRefundCard } from '@/features/admin/operations/components/OperationsRefundCard';

export const AdminRefundRequestCreatePage = () => {
  const operations = useAdminOperations({
    overview: false,
    support: false,
    refunds: false,
    stock: false,
    emails: false,
    fulfillment: false,
  });

  return (
    <PageContainer size="admin" title="Créer un remboursement">
      <OperationsHeader
        description="Crée une nouvelle demande ou un suivi manuel de remboursement."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8 grid gap-6">
        <OperationsRefundCard
          refundForm={operations.refundForm}
          setRefundForm={operations.setRefundForm}
          submitRefund={operations.submitRefund}
          withHeading={false}
        />
      </section>
    </PageContainer>
  );
};
