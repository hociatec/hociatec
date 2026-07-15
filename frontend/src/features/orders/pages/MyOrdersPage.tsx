import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
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
  cancelMyOrder,
  fetchMyOrders,
  formatOrderStatusFr,
  type OrderDto,
} from '../api';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

export const MyOrdersPage = () => {
  useDocumentTitle('Mes commandes');

  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>('idle');
  const [error, setError] = useState<string | null>(null);


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

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10" aria-busy={isLoading}>
        <header className="mb-8 space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Mon espace</p>
          <h1 className="text-3xl font-semibold text-slate-900">Mes commandes</h1>
          <p className="max-w-2xl text-slate-600">
            Suivez l&apos;historique de vos achats et consultez le détail de chaque commande pour évaluer les produits livrés.
          </p>
        </header>

        {error && (
          <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {error}
          </div>
        )}

        {!isLoading && orders.length === 0 && (
          <div className="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-600">
            Vous n&apos;avez pas encore de commande.
          </div>
        )}

        {orders.length > 0 && (
          <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table className="w-full border-collapse text-left text-sm text-slate-900">
              <thead>
                <tr className="border-b border-slate-300">
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
                  <tr key={o.id} className="border-b border-slate-200 align-top">
                    <th scope="row" className="px-4 py-3 font-medium">{o.number}</th>
                    <td className="px-4 py-3">{new Date(o.createdAt).toLocaleDateString('fr-FR')}</td>
                    <td className="px-4 py-3">{formatPrice(o.totalPriceCents)}</td>
                    <td className="px-4 py-3 capitalize">{o.statusLabel ?? formatOrderStatusFr(o.status)}</td>
                    <td className="px-4 py-3">
                      {(o.pendingReviewsCount ?? 0) > 0
                        ? `${o.pendingReviewsCount} produit${(o.pendingReviewsCount ?? 0) > 1 ? 's' : ''}`
                        : 'Aucun'}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex flex-wrap items-center gap-2">
                        <Link
                          className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                          to={`/orders/${o.id}`}
                        >
                          Voir le détail
                        </Link>

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
                                            x.id === o.id ? { ...x, status: 'cancelled', statusLabel: 'annulée' } : x,
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
