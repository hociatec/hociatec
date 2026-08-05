import { useState } from 'react';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { toOrderId } from '@/shared/types/ids';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/shared/components/ui/alert-dialog';
import { OrderDetailsDialog } from '@/features/orders/components/OrderDetailsDialog';
import { useMyOrders } from '../hooks/useMyOrders';

export const MyOrdersPage = () => {
  useDocumentTitle('Mes commandes');
  const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null);

  const {
    orders,
    ordersState,
    isLoading,
    error,
    cancellingOrderId,
    payingOrderId,
    handlePayOrder,
    handleCancelOrder,
    handleDownloadInvoice,
    handleDownloadInvoicePdf,
    handleDownloadInvoiceXml,
    retry,
  } = useMyOrders();

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10" aria-busy={isLoading}>
        <header className="mb-8 space-y-3">
          <h1 className="text-3xl font-semibold text-brand-900">Mes commandes</h1>
          <p className="max-w-2xl text-stone-600">
            Suivez l&apos;historique de vos achats, consultez vos factures et ouvrez chaque commande
            pour évaluer les produits livrés.
          </p>
        </header>

        {ordersState.status === 'loading' && <LoadingState>Chargement des commandes...</LoadingState>}
        {ordersState.status === 'error' && (
          <ErrorState onAction={() => void retry()}>{ordersState.error}</ErrorState>
        )}
        {ordersState.status !== 'error' && error && <ErrorState>{error}</ErrorState>}

        {ordersState.status === 'success' && orders.length === 0 && (
          <EmptyState>Vous n&apos;avez pas encore de commande.</EmptyState>
        )}

        {ordersState.status === 'success' && orders.length > 0 && (
          <div className="overflow-x-auto rounded-xl border border-brand-100 bg-white shadow-sm">
            <table className="w-full border-collapse text-left text-sm text-brand-900">
              <thead>
                <tr className="border-b border-brand-200">
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Commande
                  </th>
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Date
                  </th>
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Total
                  </th>
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Statut
                  </th>
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Produits à évaluer
                  </th>
                  <th scope="col" className="px-4 py-3 font-semibold">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {orders.map((o) => (
                  <tr key={o.id} className="border-b border-brand-100 align-top">
                    <th scope="row" className="px-4 py-3 font-medium">
                      {o.number}
                    </th>
                    <td className="px-4 py-3">{o.createdAtLabel}</td>
                    <td className="px-4 py-3">{o.totalPriceLabel}</td>
                    <td className="px-4 py-3">{o.statusLabel}</td>
                    <td className="px-4 py-3">{o.pendingReviewsLabel}</td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap items-center gap-2">
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                          onClick={() => setSelectedOrderId(o.id)}
                        >
                          Voir le détail
                        </button>

                        {o.canPay && (
                          <button
                            type="button"
                            className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
                            onClick={() => void handlePayOrder(o.id)}
                            disabled={payingOrderId === o.id}
                          >
                            {payingOrderId === o.id ? 'Préparation du paiement...' : 'Régler'}
                          </button>
                        )}

                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                          onClick={() => void handleDownloadInvoice(o)}
                          disabled={!o.canDownloadInvoice}
                          title={
                            !o.canDownloadInvoice
                              ? 'La facture est disponible uniquement pour une commande réglée non annulée.'
                              : undefined
                          }
                        >
                          Télécharger la facture
                        </button>

                        {o.canCancel && (
                          <AlertDialog>
                            <AlertDialogTrigger asChild>
                              <button
                                type="button"
                                className="inline-flex items-center rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:border-red-400"
                              >
                                Annuler
                              </button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                              <AlertDialogHeader>
                                <AlertDialogTitle>Confirmer l&apos;annulation</AlertDialogTitle>
                                <AlertDialogDescription>
                                  Voulez-vous annuler cette commande en attente ? Cette action est
                                  irréversible.
                                </AlertDialogDescription>
                              </AlertDialogHeader>
                              <AlertDialogFooter>
                                <AlertDialogCancel>Non</AlertDialogCancel>
                                <AlertDialogAction
                                  onClick={() => {
                                    void handleCancelOrder(o.id);
                                  }}
                                >
                                  Oui, annuler
                                </AlertDialogAction>
                              </AlertDialogFooter>
                            </AlertDialogContent>
                          </AlertDialog>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <OrderDetailsDialog
          orderId={selectedOrderId}
          open={selectedOrderId !== null}
          payingOrderId={payingOrderId}
          cancellingOrderId={cancellingOrderId}
          onClose={() => setSelectedOrderId(null)}
          onPayOrder={(orderId) => void handlePayOrder(toOrderId(orderId))}
          onCancelOrder={(orderId) => void handleCancelOrder(toOrderId(orderId))}
          onDownloadInvoicePdf={(order) => void handleDownloadInvoicePdf(order)}
          onDownloadInvoiceXml={(order) => void handleDownloadInvoiceXml(order)}
        />
      </div>
    </SiteLayout>
  );
};
