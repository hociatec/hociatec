import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { EmptyState, ErrorState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
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
import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  fetchMyOrders,
  formatOrderStatusFr,
  type OrderDto,
} from '../api';

export const MyOrdersPage = () => {
  useDocumentTitle('Mes commandes');

  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [payingOrderId, setPayingOrderId] = useState<number | null>(null);

  useEffect(() => {
    setStatus('loading');
    setError(null);
    void fetchMyOrders()
      .then((items) => {
        setOrders(items);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Erreur lors du chargement');
        setStatus('error');
      });
  }, []);

  const isLoading = status === 'loading';
  const canDownloadInvoice = (order: OrderDto) => !['pending', 'cancelled'].includes(order.status);

  const handlePayOrder = async (orderId: number) => {
    setPayingOrderId(orderId);
    setError(null);

    try {
      const result = await checkoutExistingOrder(orderId);
      if ('mode' in result && result.mode === 'redirect') {
        window.location.assign(result.checkoutUrl);
        return;
      }

      const updatedOrder = result as OrderDto;
      setOrders((prev) => prev.map((order) => (order.id === orderId ? updatedOrder : order)));
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de lancer le règlement.'));
    } finally {
      setPayingOrderId(null);
    }
  };

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10" aria-busy={isLoading}>
        <header className="mb-8 space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-stone-500">Mon espace</p>
          <h1 className="text-3xl font-semibold text-brand-900">Mes commandes</h1>
          <p className="max-w-2xl text-stone-600">
            Suivez l&apos;historique de vos achats, consultez vos factures et ouvrez chaque commande pour évaluer les produits livrés.
          </p>
        </header>

        {error && (
          <ErrorState>{error}</ErrorState>
        )}

        {!isLoading && orders.length === 0 && (
          <EmptyState>Vous n&apos;avez pas encore de commande.</EmptyState>
        )}

        {orders.length > 0 && (
          <div className="overflow-x-auto rounded-xl border border-brand-100 bg-white shadow-sm">
            <table className="w-full border-collapse text-left text-sm text-brand-900">
              <thead>
                <tr className="border-b border-brand-200">
                  <th scope="col" className="px-4 py-3 font-semibold">Commande</th>
                  <th scope="col" className="px-4 py-3 font-semibold">Date</th>
                  <th scope="col" className="px-4 py-3 font-semibold">Total</th>
                  <th scope="col" className="px-4 py-3 font-semibold">Statut</th>
                  <th scope="col" className="px-4 py-3 font-semibold">Produits à évaluer</th>
                  <th scope="col" className="px-4 py-3 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                {orders.map((o) => (
                  <tr key={o.id} className="border-b border-brand-100 align-top">
                    <th scope="row" className="px-4 py-3 font-medium">{o.number}</th>
                    <td className="px-4 py-3">{formatOptionalFrenchDate(o.createdAt)}</td>
                    <td className="px-4 py-3">{formatEuroCents(o.totalPriceCents)}</td>
                    <td className="px-4 py-3">{o.statusLabel ?? formatOrderStatusFr(o.status)}</td>
                    <td className="px-4 py-3">
                      {(o.pendingReviewsCount ?? 0) > 0
                        ? `${o.pendingReviewsCount} produit${(o.pendingReviewsCount ?? 0) > 1 ? 's' : ''}`
                        : 'Aucun'}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap items-center gap-2">
                        <Link
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                          to={`/orders/${o.id}`}
                        >
                          Voir le détail
                        </Link>

                        {o.status === 'pending' && (
                          <button
                            type="button"
                            className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
                            onClick={() => void handlePayOrder(o.id)}
                            disabled={payingOrderId === o.id}
                          >
                            {payingOrderId === o.id ? 'Redirection...' : 'Régler'}
                          </button>
                        )}

                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                          onClick={() => void downloadOrderInvoicePdf(o.id, buildOrderInvoiceFilename(o))}
                          disabled={!canDownloadInvoice(o)}
                          title={!canDownloadInvoice(o) ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}
                        >
                          Télécharger la facture
                        </button>

                        {o.status === 'pending' && (
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
                                  Voulez-vous annuler cette commande en attente ? Cette action est irréversible.
                                </AlertDialogDescription>
                              </AlertDialogHeader>
                              <AlertDialogFooter>
                                <AlertDialogCancel>Non</AlertDialogCancel>
                                <AlertDialogAction
                                  onClick={() => {
                                    void cancelMyOrder(o.id)
                                      .then(() => {
                                        setOrders((prev) =>
                                          prev.map((x) =>
                                            x.id === o.id
                                              ? {
                                                  ...x,
                                                  status: 'cancelled',
                                                  statusLabel: 'Annulée',
                                                  invoice: x.invoice ? { ...x.invoice, status: 'cancelled' } : x.invoice,
                                                }
                                              : x,
                                          ),
                                        );
                                      })
                                      .catch(() => undefined);
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
      </div>
    </SiteLayout>
  );
};
