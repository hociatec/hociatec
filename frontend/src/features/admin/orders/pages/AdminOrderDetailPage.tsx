import { useNavigate } from 'react-router-dom';

import { useAdminOrderDetail } from '@/features/admin/orders/hooks/useAdminOrderDetail';
import { AdminOrderDeliverySection } from '@/features/admin/orders/components/AdminOrderDeliverySection';
import { AdminOrderHistorySection } from '@/features/admin/orders/components/AdminOrderHistorySection';
import { AdminOrderItemsSection } from '@/features/admin/orders/components/AdminOrderItemsSection';
import { AdminOrderPaymentSection } from '@/features/admin/orders/components/AdminOrderPaymentSection';
import { AdminOrderClientAccess } from '@/features/admin/orders/components/AdminOrderClientAccess';
import { AdminOrderSummarySection } from '@/features/admin/orders/components/AdminOrderSummarySection';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';

export const AdminOrderDetailPage = () => {
  const navigate = useNavigate();
  const {
    actionMessage,
    canDownloadInvoice,
    deliveryForm,
    deliverySaving,
    error,
    events,
    order,
    processing,
    setDeliveryForm,
    status,
    regenerateInvoice,
    resendOrderEmail,
    resendStatusEmail,
    saveDelivery,
    downloadInvoicePdf,
    downloadInvoiceXml,
  } = useAdminOrderDetail();
  return (
    <PageContainer
      size="admin"
      title={order ? `Commande ${order.number}` : 'Commande'}
      headerActions={
        <button
          type="button"
          className="underline text-sm"
          onClick={() => navigate('/admin/orders')}
        >
          Retour aux commandes
        </button>
      }
    >
      {status === 'loading' && <LoadingState>Chargement...</LoadingState>}
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {actionMessage && <FeedbackMessage variant="success">{actionMessage}</FeedbackMessage>}

      {status === 'success' && order && processing && (
        <div className="space-y-6">
          <AdminOrderSummarySection
            order={order}
            processing={processing}
            canDownloadInvoice={canDownloadInvoice}
            regenerateInvoice={regenerateInvoice}
            resendOrderEmail={resendOrderEmail}
            resendStatusEmail={resendStatusEmail}
            downloadInvoicePdf={downloadInvoicePdf}
            downloadInvoiceXml={downloadInvoiceXml}
          />

          <AdminOrderPaymentSection payment={order.payment ?? null} />

          <AdminOrderDeliverySection
            order={order}
            deliveryForm={deliveryForm}
            deliverySaving={deliverySaving}
            setDeliveryForm={setDeliveryForm}
            saveDelivery={saveDelivery}
          />

          <AdminOrderItemsSection items={order.items} />

          <AdminOrderHistorySection events={events} />

          <AdminOrderClientAccess orderId={order.id} userId={order.userId} />
        </div>
      )}
    </PageContainer>
  );
};
