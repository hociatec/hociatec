import { PageContainer } from '@/shared/components/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsActionsSection } from '@/features/admin/operations/components/OperationsActionsSection';
import { OperationsRecentSection } from '@/features/admin/operations/components/OperationsRecentSection';
import { OperationsExports, OperationsHeader, OperationsPriorities } from '@/features/admin/operations/components/AdminOperationsWidgets';

const exportLabels: Record<string, string> = {
  orders: 'Commandes', customers: 'Clients', products: 'Produits', quotes: 'Devis',
  refunds: 'Remboursements', support: 'SAV',
};

export const AdminOperationsPage = () => {
  const operations = useAdminOperations();

  return (
    <PageContainer size="admin" title="Centre exploitation">
      <OperationsHeader message={operations.message} onRefresh={operations.refresh} status={operations.status} />
      {operations.overview ? (
        <OperationsPriorities failedEmails={operations.failedEmails} hasPriorities={operations.hasPriorities} overview={operations.overview} />
      ) : null}
      <OperationsActionsSection
        bulkForm={operations.bulkForm}
        fulfillmentOrders={operations.fulfillmentOrders}
        quoteConversionMessage={operations.quoteConversionMessage}
        quoteConversionStatus={operations.quoteConversionStatus}
        quoteReference={operations.quoteReference}
        refundForm={operations.refundForm}
        setBulkForm={operations.setBulkForm}
        setQuoteReference={operations.setQuoteReference}
        setRefundForm={operations.setRefundForm}
        setShippingForms={operations.setShippingForms}
        setStockForm={operations.setStockForm}
        setSupportForm={operations.setSupportForm}
        shippingForms={operations.shippingForms}
        stockForm={operations.stockForm}
        submitBulk={operations.submitBulk}
        submitQuoteConversion={operations.submitQuoteConversion}
        submitRefund={operations.submitRefund}
        submitShipOrder={operations.submitShipOrder}
        submitStock={operations.submitStock}
        submitSupport={operations.submitSupport}
        supportForm={operations.supportForm}
      />
      <OperationsRecentSection
        emails={operations.emails}
        refundConfirmations={operations.refundConfirmations}
        refunds={operations.refunds}
        setRefundConfirmations={operations.setRefundConfirmations}
        setStockThresholds={operations.setStockThresholds}
        setSupportReplies={operations.setSupportReplies}
        stock={operations.stock}
        stockThresholds={operations.stockThresholds}
        submitStockThreshold={operations.submitStockThreshold}
        submitStripeRefund={operations.submitStripeRefund}
        submitSupportReply={operations.submitSupportReply}
        support={operations.support}
        supportReplies={operations.supportReplies}
        updateRefundStatus={operations.updateRefundStatus}
        updateSupportStatus={operations.updateSupportStatus}
      />
      <OperationsExports exportLabels={exportLabels} />
    </PageContainer>
  );
};
