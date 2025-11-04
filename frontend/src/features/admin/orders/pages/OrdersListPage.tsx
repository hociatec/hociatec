import { useEffect, useState } from 'react';

import { PageContainer } from '@/shared/components/PageContainer';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/shared/components/ui/alert-dialog';

import { fetchAdminOrders, updateAdminOrderStatus, type OrderDto, formatOrderStatusFr } from '../../../orders/api';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    valueInCents / 100,
  );

export const OrdersListPage = () => {
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled'>('all');
  const [editing, setEditing] = useState<{
    id: number;
    current: OrderDto['status'];
    next: OrderDto['status'];
  } | null>(null);

  useEffect(() => {
    setStatus('loading');
    setError(null);
    void fetchAdminOrders(filter)
      .then((items) => {
        setOrders(items);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Erreur');
        setStatus('error');
      });
  }, [filter]);

  const handleConfirmUpdate = () => {
    if (!editing) return;
    const { id, next } = editing;
    void updateAdminOrderStatus(id, next as any)
      .then((updated) => {
        setOrders((prev) => prev.map((o) => (o.id === id ? updated : o)));
        setEditing(null);
      })
      .catch(() => undefined);
  };

  return (
    <PageContainer title="Commandes">
        <FilterBar>
          <SelectFilter
            value={filter}
            onChange={(v) => setFilter(v as any)}
            options={[
              { value: 'all', label: 'Tous' },
              { value: 'pending', label: 'En attente' },
              { value: 'confirmed', label: 'Confirmé' },
              { value: 'delivered', label: 'Livré' },
              { value: 'cancelled', label: 'Annulé' },
            ]}
            ariaLabel="Statut"
          />
        </FilterBar>
        {status === 'loading' && <p>Chargement...</p>}
        {error && <div className="text-red-600">{error}</div>}
        {orders.length > 0 && (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left border-b">
                <th className="py-2">#</th>
                <th className="py-2">Client</th>
                <th className="py-2">Date</th>
                <th className="py-2">Total</th>
                <th className="py-2">Statut</th>
                <th className="py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((o) => (
                <tr key={o.id} className="border-b">
                  <td className="py-2">{o.number}</td>
                  <td className="py-2">{o.shipping?.name}</td>
                  <td className="py-2">{new Date(o.createdAt).toLocaleDateString('fr-FR')}</td>
                  <td className="py-2">{formatPrice(o.totalPriceCents)}</td>
                  <td className="py-2 capitalize">{o.statusLabel ?? formatOrderStatusFr(o.status)}</td>
                  <td className="py-2">
                    <button
                      type="button"
                      className="underline"
                      onClick={() => setEditing({ id: o.id, current: o.status, next: o.status })}
                    >
                      Modifier le statut
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <AlertDialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Modifier le statut</AlertDialogTitle>
              <AlertDialogDescription>
                Choisissez le nouveau statut de la commande.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <div className="space-y-2">
              <label className="block">
                <input
                  type="radio"
                  name="order-status"
                  value="pending"
                  checked={editing?.next === 'pending'}
                  onChange={() => setEditing((e) => (e ? { ...e, next: 'pending' } : e))}
                />{' '}
                {formatOrderStatusFr('pending')}
              </label>
              <label className="block">
                <input
                  type="radio"
                  name="order-status"
                  value="confirmed"
                  checked={editing?.next === 'confirmed'}
                  onChange={() => setEditing((e) => (e ? { ...e, next: 'confirmed' } : e))}
                />{' '}
                {formatOrderStatusFr('confirmed')}
              </label>
              <label className="block">
                <input
                  type="radio"
                  name="order-status"
                  value="delivered"
                  checked={editing?.next === 'delivered'}
                  onChange={() => setEditing((e) => (e ? { ...e, next: 'delivered' } : e))}
                />{' '}
                {formatOrderStatusFr('delivered')}
              </label>
              <label className="block">
                <input
                  type="radio"
                  name="order-status"
                  value="cancelled"
                  checked={editing?.next === 'cancelled'}
                  onChange={() => setEditing((e) => (e ? { ...e, next: 'cancelled' } : e))}
                />{' '}
                {formatOrderStatusFr('cancelled')}
              </label>
            </div>
            <AlertDialogFooter>
              <AlertDialogCancel>Annuler</AlertDialogCancel>
              <AlertDialogAction onClick={handleConfirmUpdate}>Enregistrer</AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
  );
};

