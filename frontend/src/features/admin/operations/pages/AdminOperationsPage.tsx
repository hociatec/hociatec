import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import { OperationsActionsSection } from '@/features/admin/operations/components/OperationsActionsSection';
import { OperationsRecentSection } from '@/features/admin/operations/components/OperationsRecentSection';
import {
  OperationsExports,
  OperationsHeader,
  OperationsPriorities,
} from '@/features/admin/operations/components/AdminOperationsWidgets';

const exportLabels: Record<string, string> = {
  orders: 'Commandes',
  customers: 'Clients',
  products: 'Produits',
  quotes: 'Devis',
  refunds: 'Remboursements',
  support: 'SAV',
};

export const AdminOperationsPage = () => {
  const operations = useAdminOperations();

  return (
    <PageContainer size="admin" title="Centre exploitation">
      <OperationsHeader
        message={operations.message}
        onRefresh={operations.refresh}
        status={operations.status}
      />
      {operations.overview ? (
        <OperationsPriorities
          failedEmails={operations.failedEmails}
          hasPriorities={operations.hasPriorities}
          overview={operations.overview}
        />
      ) : null}
      <OperationsActionsSection
        bulkForm={operations.bulkForm}
        fulfillmentMeta={operations.fulfillmentMeta}
        fulfillmentOrders={operations.fulfillmentOrders}
        quoteConversionMessage={operations.quoteConversionMessage}
        quoteConversionStatus={operations.quoteConversionStatus}
        quoteReference={operations.quoteReference}
        refundForm={operations.refundForm}
        setBulkForm={operations.setBulkForm}
        setFulfillmentPage={operations.setFulfillmentPage}
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
        emailsMeta={operations.emailsMeta}
        refundConfirmations={operations.refundConfirmations}
        refunds={operations.refunds}
        refundsMeta={operations.refundsMeta}
        setEmailsPage={operations.setEmailsPage}
        setRefundConfirmations={operations.setRefundConfirmations}
        setRefundsPage={operations.setRefundsPage}
        setStockPage={operations.setStockPage}
        setStockThresholds={operations.setStockThresholds}
        setSupportReplies={operations.setSupportReplies}
        stock={operations.stock}
        stockMeta={operations.stockMeta}
        stockThresholds={operations.stockThresholds}
        submitStockThreshold={operations.submitStockThreshold}
        submitStripeRefund={operations.submitStripeRefund}
        submitSupportReply={operations.submitSupportReply}
        support={operations.support}
        supportMeta={operations.supportMeta}
        supportReplies={operations.supportReplies}
        setSupportPage={operations.setSupportPage}
        updateRefundStatus={operations.updateRefundStatus}
        updateSupportStatus={operations.updateSupportStatus}
      />
      <OperationsExports exportLabels={exportLabels} />
    </PageContainer>
  );
};
