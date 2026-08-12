import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsSupportCard } from '@/features/admin/operations/components/OperationsSupportCard';

export const AdminSupportRequestCreatePage = () => {
  const operations = useAdminOperations({
    overview: false,
    support: false,
    refunds: false,
    stock: false,
    emails: false,
    fulfillment: false,
  });

  return (
    <PageContainer size="admin" title="Créer un dossier SAV">
      <OperationsHeader
        description="Ouvre un nouveau dossier SAV pour un client ou une commande."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8 grid gap-6">
        <OperationsSupportCard
          supportForm={operations.supportForm}
          setSupportForm={operations.setSupportForm}
          submitSupport={operations.submitSupport}
          withHeading={false}
        />
      </section>
    </PageContainer>
  );
};
