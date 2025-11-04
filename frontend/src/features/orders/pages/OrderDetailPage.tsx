import { useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import { cancelMyOrder, fetchOrderById, formatOrderStatusFr, type OrderDto } from '../api';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/shared/components/ui/alert-dialog';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    valueInCents / 100,
  );

export const OrderDetailPage = () => {
  const { orderId } = useParams();
  const [params] = useSearchParams();
  useDocumentTitle('Detail de la commande');

  const [order, setOrder] = useState<OrderDto | null>(null);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>(
    'idle',
  );
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!orderId) return;
    setStatus('loading');
    setError(null);
    void fetchOrderById(Number(orderId))
      .then((o) => {
        setOrder(o);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Erreur');
        setStatus('error');
      });
  }, [orderId]);

  const isLoading = status === 'loading';
  const justConfirmed = params.get('confirmed') === '1';

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Detail de la commande</h1>
        {isLoading && <p>Chargement...</p>}
        {error && <div className="text-red-600">{error}</div>}
        {justConfirmed && (
          <div className="mb-4 p-3 rounded bg-green-50 text-green-800">
            Merci, votre commande a bien ete enregistree.
          </div>
        )}
        {order && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <div className="font-medium">Commande {order.number}</div>
                <div className="text-sm text-gray-600">
                  Passee le {new Date(order.createdAt).toLocaleDateString('fr-FR')}
                </div>
              </div>
              <div className="text-right space-y-2">
                <div className="font-semibold">{formatPrice(order.totalPriceCents)}</div>
                <div className="text-sm capitalize">Statut: {order.statusLabel ?? formatOrderStatusFr(order.status)}</div>
                {order.status === 'pending' && (
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button type="button" className="underline text-red-600">
                        Annuler la commande
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
                            if (!order) return;
                            void cancelMyOrder(order.id)
                              .then((updated) => setOrder(updated))
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

            <div>
              <h2 className="font-semibold mb-2">Livraison</h2>
              <div className="text-sm">
                <div>{order.shipping.name}</div>
                <div>{order.shipping.address}</div>
                <div>
                  {order.shipping.postalCode} {order.shipping.city}
                </div>
              </div>
            </div>

            <div>
              <h2 className="font-semibold mb-2">Articles</h2>
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b">
                    <th className="py-2">Produit</th>
                    <th className="py-2">SKU</th>
                    <th className="py-2">Prix unitaire</th>
                    <th className="py-2">Quantite</th>
                    <th className="py-2 text-right">Sous-total</th>
                  </tr>
                </thead>
                <tbody>
                  {order.items.map((it, idx) => (
                    <tr key={idx} className="border-b">
                      <td className="py-2">{it.productName}</td>
                      <td className="py-2">{it.productSku}</td>
                      <td className="py-2">{formatPrice(it.unitPriceCents)}</td>
                      <td className="py-2">{it.quantity}</td>
                      <td className="py-2 text-right">{formatPrice(it.linePriceCents)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
