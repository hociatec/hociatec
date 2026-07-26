import { useOrderDetail } from '@/features/orders/hooks/useOrderDetail';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { ErrorState, FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { OrderInvoiceCard } from '@/features/orders/components/OrderInvoiceCard';
import { OrderItemsReviewTable } from '@/features/orders/components/OrderItemsReviewTable';
import { OrderDetailSummary } from '@/features/orders/components/OrderDetailSummary';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const OrderDetailPage = () => {
  useDocumentTitle('Détail de la commande');
  const {
    canDownloadInvoice,
    error,
    getReviewForm,
    handlePayOrder,
    handleSubmitReview,
    handleCancelOrder,
    handleDownloadInvoicePdf,
    handleDownloadInvoiceXml,
    isLoading,
    justConfirmed,
    order,
    paying,
    updateReviewForm,
  } = useOrderDetail();
  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="mb-4 text-2xl font-semibold">Détail de la commande</h1>
        {isLoading && <LoadingState>Chargement de la commande...</LoadingState>}
        {error && <ErrorState>{error}</ErrorState>}
        {justConfirmed && (
          <FeedbackMessage variant="success" className="mb-4">
            Merci, votre commande a bien été validée et confirmée.
          </FeedbackMessage>
        )}
        {order && (
          <div className="space-y-6">
            <OrderDetailSummary
              order={order}
              onPay={() => void handlePayOrder()}
              onCancel={() => void handleCancelOrder()}
              paying={paying}
            />

            {order.invoice && <OrderInvoiceCard invoice={order.invoice} canDownloadInvoice={canDownloadInvoice} onDownloadPdf={() => void handleDownloadInvoicePdf()} onDownloadXml={() => void handleDownloadInvoiceXml()} />}

            <div>
              <h2 className="mb-2 font-semibold">Livraison</h2>
              <div className="text-sm">
                <div>
                  {order.customerDisplayName || order.invoice?.billingName || order.shipping.name}
                </div>
                <div>{order.shipping.address}</div>
                <div>
                  {order.shipping.postalCode} {order.shipping.city}
                </div>
              </div>
              {order.delivery ? (
                <div className="mt-4 rounded-2xl border border-brand-100 bg-brand-50 p-4 text-sm text-stone-700">
                  <div className="font-semibold text-brand-900">
                    {order.delivery.statusLabel ?? 'Préparation en cours'}
                  </div>
                  <div className="mt-2">
                    <span className="font-medium text-brand-900">Transporteur</span> :{' '}
                    {order.delivery.carrier || '-'}
                  </div>
                  <div>
                    <span className="font-medium text-brand-900">Numéro de suivi</span> :{' '}
                    {order.delivery.trackingNumber || '-'}
                  </div>
                  <div>
                    <span className="font-medium text-brand-900">Date estimée</span> :{' '}
                    {formatOptionalFrenchDate(order.delivery.estimatedAt)}
                  </div>
                  <div>
                    <span className="font-medium text-brand-900">Expédiée le</span> :{' '}
                    {formatOptionalFrenchDate(order.delivery.shippedAt)}
                  </div>
                  <div>
                    <span className="font-medium text-brand-900">Livrée le</span> :{' '}
                    {formatOptionalFrenchDate(order.delivery.deliveredAt)}
                  </div>
                  {order.delivery.trackingUrl ? (
                    <div className="mt-3">
                      <a
                        href={order.delivery.trackingUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                      >
                        Suivre le colis
                      </a>
                    </div>
                  ) : null}
                </div>
              ) : null}
            </div>

            <OrderItemsReviewTable
              items={order.items}
              getReviewForm={getReviewForm}
              handleSubmitReview={handleSubmitReview}
              updateReviewForm={updateReviewForm}
            />
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
