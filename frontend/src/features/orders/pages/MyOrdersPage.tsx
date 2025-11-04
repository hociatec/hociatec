import { Link } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import { fetchMyOrders, cancelMyOrder, formatOrderStatusFr, type OrderDto } from '../api';
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
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Mes commandes</h1>
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
