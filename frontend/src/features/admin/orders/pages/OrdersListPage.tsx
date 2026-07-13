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
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

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
        const message = e instanceof Error ? e.message : 'Impossible de mettre à jour le statut.';
        setUpdateError(message);
      });
  };

  return (
    <PageContainer title="Commandes">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {orders.length} commande{orders.length > 1 ? 's' : ''} chargée{orders.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-slate-500">
          Suivez les transitions de statut et les commandes en cours.
        </p>
      </div>

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

      {status === 'loading' && (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement...
        </div>
      )}
      {error && <div className="register-form__alert">{error}</div>}

      {status === 'success' && orders.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucune commande.
        </div>
      ) : null}

      {orders.length > 0 && (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th>
                <th>Date</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((o) => (
                <tr key={o.id}>
                  <td>{o.number}</td>
                  <td>{o.shipping?.name}</td>
                  <td>{new Date(o.createdAt).toLocaleDateString('fr-FR')}</td>
                  <td>{formatPrice(o.totalPriceCents)}</td>
                  <td className="capitalize">{o.statusLabel ?? formatOrderStatusFr(o.status)}</td>
                  <td>
                    {getNextStatuses(o.status).length === 0 ? (
                      <span className="text-xs text-slate-500">Statut final</span>
                    ) : (
                      <button
                        type="button"
                        className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
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
        </div>
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
