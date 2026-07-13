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

type OrderStatus = 'pending' | 'confirmed' | 'delivered' | 'cancelled';

const nextStatusMap: Record<OrderStatus, Array<OrderStatus>> = {
  pending: ['confirmed', 'cancelled'],
  confirmed: ['delivered'],
  delivered: [],
  cancelled: [],
};

const getNextStatuses = (status: OrderDto['status']): Array<OrderStatus> =>
  nextStatusMap[status as OrderStatus] ?? [];

export const OrdersListPage = () => {
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled'>('all');
  const [editing, setEditing] = useState<{
    id: number;
    current: OrderStatus;
    next: OrderStatus;
    options: Array<OrderStatus>;
  } | null>(null);
  const [updateError, setUpdateError] = useState<string | null>(null);

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
    if (editing.options.length === 0) {
      setUpdateError('Aucune transition disponible pour ce statut.');
      return;
    }
    const { id, next } = editing;
    void updateAdminOrderStatus(id, next)
      .then((updated) => {
        setOrders((prev) => prev.map((o) => (o.id === id ? updated : o)));
        setEditing(null);
        setUpdateError(null);
      })
      .catch((e: unknown) => {
        const message = e instanceof Error ? e.message : 'Impossible de mettre a jour le statut.';
        setUpdateError(message);
      });
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
                    {getNextStatuses(o.status).length === 0 ? (
                      <span className="text-xs text-slate-500">Statut final</span>
                    ) : (
                      <button
                        type="button"
                        className="underline"
                      onClick={() => {
                        const options = getNextStatuses(o.status);
                        if (!options.length) return;
                        setEditing({
                          id: o.id,
                          current: (o.status as OrderStatus) ?? 'pending',
                          next: options[0],
                          options,
                        });
                      }}
                    >
                        Modifier le statut
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <AlertDialog
          open={editing !== null}
          onOpenChange={(open) => {
            if (!open) {
              setEditing(null);
              setUpdateError(null);
            }
          }}
        >
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Modifier le statut</AlertDialogTitle>
              <AlertDialogDescription>
                Choisissez le nouveau statut de la commande.
              </AlertDialogDescription>
            </AlertDialogHeader>
            {editing?.options.length ? (
              <div className="space-y-2">
                {editing.options.map((option) => (
                  <label className="block" key={option}>
                    <input
                      type="radio"
                      name="order-status"
                      value={option}
                      checked={editing?.next === option}
                      onChange={() => setEditing((e) => (e ? { ...e, next: option } : e))}
                    />{' '}
                    {formatOrderStatusFr(option)}
                  </label>
                ))}
              </div>
            ) : (
              <p className="text-sm text-slate-500">Aucun changement possible depuis ce statut.</p>
            )}
            {updateError && <div className="text-sm text-red-600">{updateError}</div>}
            <AlertDialogFooter>
              <AlertDialogCancel>Annuler</AlertDialogCancel>
              <AlertDialogAction onClick={handleConfirmUpdate} disabled={!editing?.options.length}>
                Enregistrer
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
  );
};
