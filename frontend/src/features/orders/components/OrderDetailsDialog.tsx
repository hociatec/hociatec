import { X } from 'lucide-react';
import { Link } from 'react-router';
import { useQuery } from '@tanstack/react-query';

import { fetchOrderById, type OrderDto } from '@/features/orders/api';
import { OrderDetailSummary } from '@/features/orders/components/OrderDetailSummary';
import { OrderInvoiceCard } from '@/features/orders/components/OrderInvoiceCard';
import {
  canCancelOrderStatus,
  canDownloadInvoiceForOrderStatus,
  canPayOrderStatus,
} from '@/features/orders/models/orderModel';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { orderQueryKeys } from '@/shared/lib/queryKeys';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

type OrderDetailsDialogProps = {
  orderId: number | null;
  open: boolean;
  payingOrderId: number | null;
  cancellingOrderId: number | null;
  onClose: () => void;
  onCancelOrder: (orderId: number) => void;
  onDownloadInvoicePdf: (order: OrderDto) => void;
  onDownloadInvoiceXml: (order: OrderDto) => void;
  onPayOrder: (orderId: number) => void;
};

export const OrderDetailsDialog = ({
  cancellingOrderId,
  onCancelOrder,
  onClose,
  onDownloadInvoicePdf,
  onDownloadInvoiceXml,
  onPayOrder,
  open,
  orderId,
  payingOrderId,
}: OrderDetailsDialogProps) => {
  const detailQuery = useQuery({
    enabled: open && orderId !== null,
    queryKey: orderQueryKeys.detail(orderId),
    queryFn: () => fetchOrderById(orderId as number),
  });
  const order = detailQuery.data ?? null;

  return (
    <Dialog open={open} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 overflow-y-auto px-4 py-6">
        <div className="flex min-h-full items-center justify-center">
          <DialogPanel className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-brand-100 bg-white shadow-2xl">
            <header className="flex items-start justify-between gap-4 border-b border-brand-100 px-5 py-4">
              <div>
                <DialogTitle className="text-xl font-semibold text-brand-900">
                  {order ? `Commande ${order.number}` : 'Détail de la commande'}
                </DialogTitle>
                {order ? (
                  <p className="mt-1 text-sm text-stone-500">
                    Passée le {formatOptionalFrenchDate(order.createdAt)}
                  </p>
                ) : null}
              </div>
              <button
                type="button"
                className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-brand-100 text-stone-600 transition hover:border-brand-300 hover:text-brand-900"
                onClick={onClose}
                aria-label="Fermer le détail de la commande"
              >
                <X className="h-5 w-5" aria-hidden="true" />
              </button>
            </header>

            <div className="overflow-y-auto px-5 py-5">
              {detailQuery.isLoading ? <LoadingState>Chargement de la commande...</LoadingState> : null}
              {detailQuery.isError ? (
                <ErrorState onAction={() => void detailQuery.refetch()}>
                  Impossible de charger la commande.
                </ErrorState>
              ) : null}
              {order ? (
                <div className="space-y-6">
                  <OrderDetailSummary
                    order={order}
                    onPay={() => onPayOrder(order.id)}
                    onCancel={() => onCancelOrder(order.id)}
                    canCancel={canCancelOrderStatus(order.status)}
                    canPay={canPayOrderStatus(order.status)}
                    cancelling={cancellingOrderId === order.id}
                    paying={payingOrderId === order.id}
                  />

                  {order.invoice ? (
                    <OrderInvoiceCard
                      invoice={order.invoice}
                      canDownloadInvoice={canDownloadInvoiceForOrderStatus(order.status)}
                      onDownloadPdf={() => onDownloadInvoicePdf(order)}
                      onDownloadXml={() => onDownloadInvoiceXml(order)}
                    />
                  ) : null}

                  <section>
                    <h2 className="mb-2 font-semibold text-brand-900">Livraison</h2>
                    <div className="text-sm text-stone-700">
                      <div>{order.customerDisplayName || order.invoice?.billingName || order.shipping.name}</div>
                      <div>{order.shipping.address}</div>
                      <div>
                        {order.shipping.postalCode} {order.shipping.city}
                      </div>
                    </div>
                    {order.delivery ? (
                      <div className="mt-4 rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-stone-700">
                        <div className="font-semibold text-brand-900">{order.delivery.statusLabel}</div>
                        <div className="mt-2">Transporteur : {order.delivery.carrier || '-'}</div>
                        <div>Numéro de suivi : {order.delivery.trackingNumber || '-'}</div>
                        <div>Date estimée : {formatOptionalFrenchDate(order.delivery.estimatedAt)}</div>
                        <div>Expédiée le : {formatOptionalFrenchDate(order.delivery.shippedAt)}</div>
                        <div>Livrée le : {formatOptionalFrenchDate(order.delivery.deliveredAt)}</div>
                      </div>
                    ) : null}
                  </section>

                  <section>
                    <h2 className="mb-3 font-semibold text-brand-900">Articles</h2>
                    {order.items.length > 0 ? (
                      <div className="overflow-x-auto rounded-xl border border-brand-100">
                        <table className="w-full border-collapse text-left text-sm">
                          <thead>
                            <tr className="border-b border-brand-100 bg-brand-50 text-brand-900">
                              <th scope="col" className="px-4 py-3 font-semibold">Produit</th>
                              <th scope="col" className="px-4 py-3 font-semibold">Quantité</th>
                              <th scope="col" className="px-4 py-3 font-semibold">Prix unitaire</th>
                              <th scope="col" className="px-4 py-3 font-semibold">Total</th>
                            </tr>
                          </thead>
                          <tbody>
                            {order.items.map((item) => (
                              <tr key={item.orderItemId} className="border-b border-brand-50">
                                <th scope="row" className="px-4 py-3 font-medium text-brand-900">
                                  <div>{item.productName}</div>
                                  <div className="text-xs font-normal text-stone-500">{item.productSku}</div>
                                </th>
                                <td className="px-4 py-3">{item.quantity}</td>
                                <td className="px-4 py-3">{formatEuroCents(item.unitPriceCents)}</td>
                                <td className="px-4 py-3">{formatEuroCents(item.linePriceCents)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <EmptyState>Aucun article dans cette commande.</EmptyState>
                    )}
                  </section>
                </div>
              ) : null}
            </div>

            {order ? (
              <footer className="flex flex-wrap justify-end gap-3 border-t border-brand-100 px-5 py-4">
                <Link
                  to={`/orders/${order.id}`}
                  className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                  onClick={onClose}
                >
                  Ouvrir la page complète
                </Link>
                <button
                  type="button"
                  className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
                  onClick={onClose}
                >
                  Fermer
                </button>
              </footer>
            ) : null}
          </DialogPanel>
        </div>
      </div>
    </Dialog>
  );
};
