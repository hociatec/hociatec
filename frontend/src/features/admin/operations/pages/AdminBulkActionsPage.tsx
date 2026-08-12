import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsQuickActionsCard } from '@/features/admin/operations/components/OperationsQuickActionsCard';

export const AdminBulkActionsPage = () => {
  const operations = useAdminOperations({ overview: false, support: false, refunds: false, stock: false, emails: false, fulfillment: false });

  return (
    <PageContainer size="admin" title="Actions groupées">
      <OperationsHeader
        description="Mises à jour massives de commandes et conversion rapide d’un devis en commande."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8 grid gap-6">
        <OperationsQuickActionsCard
          bulkForm={operations.bulkForm}
          quoteConversionMessage={operations.quoteConversionMessage}
          quoteConversionStatus={operations.quoteConversionStatus}
          quoteReference={operations.quoteReference}
          setBulkForm={operations.setBulkForm}
          setQuoteReference={operations.setQuoteReference}
          submitBulk={operations.submitBulk}
          submitQuoteConversion={operations.submitQuoteConversion}
        />
      </section>
    </PageContainer>
  );
};
