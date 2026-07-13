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
  fetchPendingReviews,
  formatOrderStatusFr,
  type OrderDto,
  type PendingReviewDto,
} from '../api';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

export const MyOrdersPage = () => {
  useDocumentTitle('Mes commandes');

  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [pendingReviews, setPendingReviews] = useState<PendingReviewDto[]>([]);
  const [pendingError, setPendingError] = useState<string | null>(null);

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

  useEffect(() => {
    setPendingError(null);
    void fetchPendingReviews()
      .then((items) => setPendingReviews(items))
      .catch((e: unknown) =>
        setPendingError(e instanceof Error ? e.message : 'Impossible de charger les avis en attente'),
      );
  }, []);

  const isLoading = status === 'loading';

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10">
        <header className="mb-8 space-y-3">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Mon espace</p>
          <h1 className="text-3xl font-semibold text-slate-900">Mes commandes</h1>
          <p className="max-w-2xl text-slate-600">
            Suivez l&apos;historique de vos achats, vos statuts de commande et les avis en attente.
          </p>
        </header>

        {pendingError && (
          <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {pendingError}
          </div>
        )}

        {pendingReviews.length > 0 && (
          <section className="mb-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">Avis en attente</h2>
            <p className="mt-1 text-sm text-slate-600">
              Après réception de la commande, vous pouvez ajouter votre retour sur les produits concernés.
            </p>
            <ul className="mt-4 space-y-3">
              {pendingReviews.map((pending) => (
                <li
                  key={pending.orderItemId}
                  className="flex flex-col gap-3 rounded-2xl border border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="space-y-1">
                    <div className="font-medium text-slate-900">{pending.product?.name ?? 'Produit'}</div>
                    <div className="text-sm text-slate-600">
                      Commande {pending.orderNumber} ·{' '}
                      {new Date(pending.orderCreatedAt).toLocaleDateString('fr-FR')}
                    </div>
                  </div>
                  <Link
                    className="inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                    to={`/orders/${pending.orderId}?review=${pending.orderItemId}`}
                  >
                    Donner mon avis
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        )}

        {isLoading && <p className="text-slate-600">Chargement de vos commandes...</p>}

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
          <ul className="space-y-4">
            {orders.map((o) => (
              <li key={o.id} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div>
                    <div className="font-medium text-slate-900">Commande {o.number}</div>
                    <div className="text-sm text-slate-600">
                      Le {new Date(o.createdAt).toLocaleDateString('fr-FR')}
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="font-semibold text-slate-900">{formatPrice(o.totalPriceCents)}</div>
                    <div className="text-sm capitalize text-slate-600">
                      Statut: {o.statusLabel ?? formatOrderStatusFr(o.status)}
                    </div>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <Link
                      className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                      to={`/orders/${o.id}`}
                    >
                      Voir le détail
                    </Link>
                    {o.hasPendingReviews && (
                      <Link
                        className="inline-flex items-center rounded-full border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-400"
                        to={`/orders/${o.id}`}
                      >
                        Donner mon avis
                      </Link>
                    )}
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
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </SiteLayout>
  );
};
