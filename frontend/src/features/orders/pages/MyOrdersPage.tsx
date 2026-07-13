import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import { fetchMyOrders, cancelMyOrder, formatOrderStatusFr, type OrderDto, fetchPendingReviews, type PendingReviewDto } from '../api';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/shared/components/ui/alert-dialog';
import { useEffect, useState } from 'react';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    valueInCents / 100,
  );

export const MyOrdersPage = () => {
  useDocumentTitle('Mes commandes');

  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>(
    'idle',
  );
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
      .catch((e: unknown) => setPendingError(e instanceof Error ? e.message : 'Impossible de charger les avis en attente'));
  }, []);

  const isLoading = status === 'loading';

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Mes commandes</h1>
        {pendingError && <div className="text-red-600 mb-4">{pendingError}</div>}
        {pendingReviews.length > 0 && (
          <div className="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h2 className="text-lg font-semibold mb-2">Avis en attente</h2>
            <ul className="space-y-3">
              {pendingReviews.map((pending) => (
                <li key={pending.orderItemId} className="flex items-center justify-between gap-4 text-sm">
                  <div>
                    <div className="font-medium">{pending.product?.name ?? 'Produit'}</div>
                    <div className="text-gray-600">
                      Commande {pending.orderNumber} ·{' '}
                      {new Date(pending.orderCreatedAt).toLocaleDateString('fr-FR')}
                    </div>
                  </div>
                  <Link
                    className="text-blue-600 underline"
                    to={`/orders/${pending.orderId}?review=${pending.orderItemId}`}
                  >
                    Donner mon avis
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        )}
        {isLoading && <p>Chargement de vos commandes...</p>}
        {error && <div className="text-red-600">{error}</div>}
        {!isLoading && orders.length === 0 && (
          <p>Vous n'avez pas encore de commande.</p>
        )}
        {orders.length > 0 && (
          <ul className="divide-y divide-gray-200">
            {orders.map((o) => (
              <li key={o.id} className="py-4 flex items-center justify-between gap-4">
                <div>
                  <div className="font-medium">Commande {o.number}</div>
                  <div className="text-sm text-gray-600">
                    Le {new Date(o.createdAt).toLocaleDateString('fr-FR')}
                  </div>
                </div>
                <div className="text-right">
                  <div className="font-semibold">{formatPrice(o.totalPriceCents)}</div>
                  <div className="text-sm capitalize">Statut: {o.statusLabel ?? formatOrderStatusFr(o.status)}</div>
                </div>
                <div className="ml-4">
                  <Link className="underline" to={`/orders/${o.id}`}>
                    Voir le detail
                  </Link>
                  {o.hasPendingReviews && (
                    <div>
                      <Link className="text-blue-600 underline text-sm" to={`/orders/${o.id}`}>
                        Donner mon avis
                      </Link>
                    </div>
                  )}
                </div>
                {o.status === 'pending' && (
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button
                        type="button"
                        className="underline text-red-600"
                      >
                        Annuler
                      </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Confirmer l'annulation</AlertDialogTitle>
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
                                setOrders((prev) => prev.map((x) => (x.id === o.id ? { ...x, status: 'cancelled', statusLabel: 'annulée' } : x)));
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
              </li>
            ))}
          </ul>
        )}
      </div>
    </SiteLayout>
  );
};
