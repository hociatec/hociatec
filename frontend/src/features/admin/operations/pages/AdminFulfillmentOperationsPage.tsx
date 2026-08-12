import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsHeader } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsShippingQueue } from '@/features/admin/operations/components/OperationsShippingQueue';

export const AdminFulfillmentOperationsPage = () => {
  const operations = useAdminOperations({ fulfillment: true, overview: false, support: false, refunds: false, stock: false, emails: false });

  return (
    <PageContainer size="admin" title="Expéditions">
      <OperationsHeader
        description="Préparation des commandes, saisie du suivi transporteur et passage en expédié."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8 grid gap-6">
        <OperationsShippingQueue
          fulfillmentMeta={operations.fulfillmentMeta}
          fulfillmentOrders={operations.fulfillmentOrders}
          setFulfillmentPage={operations.setFulfillmentPage}
          shippingForms={operations.shippingForms}
          setShippingForms={operations.setShippingForms}
          submitShipOrder={operations.submitShipOrder}
        />
      </section>
    </PageContainer>
  );
};
