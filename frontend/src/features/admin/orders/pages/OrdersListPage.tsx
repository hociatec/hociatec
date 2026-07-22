import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
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
import { fetchAdminOrders, updateAdminOrderStatus, type OrderDto, formatInvoiceStatusFr, formatOrderStatusFr, formatPaymentStatusFr, formatStripePaymentStatusFr } from '../../../orders/api';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatOptionalFrenchDate, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type OrderStatus = 'pending' | 'confirmed' | 'delivered' | 'cancelled';
type SortKey = 'newest' | 'oldest' | 'amount_desc' | 'amount_asc' | 'customer_asc';

const nextStatusMap: Record<OrderStatus, Array<OrderStatus>> = {
  pending: ['confirmed', 'cancelled'],
  confirmed: ['delivered'],
  delivered: [],
  cancelled: [],
};

const getNextStatuses = (status: OrderDto['status']): Array<OrderStatus> =>
  nextStatusMap[status as OrderStatus] ?? [];

const getOrderCustomerLabel = (order: OrderDto) =>
  order.customerDisplayName
  || order.invoice?.billingName
  || order.shipping?.name
  || order.invoice?.billingEmail
  || 'Client inconnu';

const getPaymentLabel = (order: OrderDto) => {
  if (!order.payment) {
    return 'Aucun';
  }

  return order.payment.statusLabel
    ?? formatPaymentStatusFr(order.payment.status);
};

export const OrdersListPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled'>(
    (searchParams.get('status') as 'all' | 'pending' | 'confirmed' | 'delivered' | 'cancelled' | null) ?? 'all',
  );
  const [health, setHealth] = useState<'all' | 'issues'>(
    (searchParams.get('health') as 'all' | 'issues' | null) ?? 'all',
  );
  const [search, setSearch] = useState(searchParams.get('search') ?? '');
  const [sort, setSort] = useState<SortKey>(
    (searchParams.get('sort') as SortKey | null) ?? 'newest',
  );
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
    void fetchAdminOrders(filter, health)
      .then((items) => {
        setOrders(items);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Erreur');
        setStatus('error');
      });
  }, [filter, health]);

  useEffect(() => {
    const next = new URLSearchParams();
    if (filter !== 'all') next.set('status', filter);
    if (health !== 'all') next.set('health', health);
    if (search.trim() !== '') next.set('search', search.trim());
    if (sort !== 'newest') next.set('sort', sort);
    setSearchParams(next, { replace: true });
  }, [filter, health, search, sort, setSearchParams]);

  const filteredOrders = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();
    const items = orders.filter((order) => {
      if (normalizedSearch === '') {
        return true;
      }

      const haystack = [
        order.number,
        getOrderCustomerLabel(order),
        order.invoice?.billingEmail,
        order.invoice?.billingCompany,
        order.invoice?.number,
        order.shipping?.address,
        order.shipping?.city,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

      return haystack.includes(normalizedSearch);
    });

    return [...items].sort((left, right) => {
      switch (sort) {
        case 'oldest':
          return new Date(left.createdAt).getTime() - new Date(right.createdAt).getTime();
        case 'amount_desc':
          return right.totalPriceCents - left.totalPriceCents;
        case 'amount_asc':
          return left.totalPriceCents - right.totalPriceCents;
        case 'customer_asc':
          return getOrderCustomerLabel(left).localeCompare(getOrderCustomerLabel(right), 'fr', { sensitivity: 'base' });
        case 'newest':
        default:
          return new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime();
      }
    });
  }, [orders, search, sort]);

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
    <PageContainer size="admin" title="Commandes">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filteredOrders.length} commande{filteredOrders.length > 1 ? 's' : ''} affichée{filteredOrders.length > 1 ? 's' : ''} sur {orders.length}.
        </p>
        <p className="text-sm text-stone-500">
          Recherchez une commande par numéro, client, email, société ou facture, puis triez la liste selon votre besoin.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher un client, une commande, un email, une facture..."
        />
        <SelectFilter
          value={filter}
          onChange={(v) => setFilter(v as typeof filter)}
          options={[
            { value: 'all', label: 'Tous les statuts' },
            { value: 'pending', label: 'En attente' },
            { value: 'confirmed', label: 'Confirmé' },
            { value: 'delivered', label: 'Livré' },
            { value: 'cancelled', label: 'Annulé' },
          ]}
          ariaLabel="Filtre statut"
        />
        <SelectFilter
          value={sort}
          onChange={(v) => setSort(v as SortKey)}
          options={[
            { value: 'newest', label: 'Plus récentes' },
            { value: 'oldest', label: 'Plus anciennes' },
            { value: 'amount_desc', label: 'Montant décroissant' },
            { value: 'amount_asc', label: 'Montant croissant' },
            { value: 'customer_asc', label: 'Client A → Z' },
          ]}
          ariaLabel="Tri commandes"
        />
        <SelectFilter
          value={health}
          onChange={(v) => setHealth(v as typeof health)}
          options={[
            { value: 'all', label: 'Toutes' },
            { value: 'issues', label: 'Avec incident de traitement' },
          ]}
          ariaLabel="Filtre incident"
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={status === 'loading'}
        isEmpty={status === 'success' && filteredOrders.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucune commande ne correspond aux filtres actuels."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Commande</th>
                <th scope="col">Client</th>
                <th scope="col">Date</th>
                <th scope="col">Facture</th>
                <th scope="col">Paiement</th>
                <th scope="col">Total</th>
                <th scope="col">Statut</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredOrders.map((order) => (
                <tr key={order.id}>
                  <th scope="row">
                    <div className="font-semibold text-brand-900">{order.number}</div>
                    {order.invoice?.purchaseOrderNumber ? (
                      <div className="muted">BC: {order.invoice.purchaseOrderNumber}</div>
                    ) : null}
                  </th>
                  <td>
                    <div className="font-medium text-brand-900">{getOrderCustomerLabel(order)}</div>
                    {order.invoice?.billingCompany ? (
                      <div className="muted">{order.invoice.billingCompany}</div>
                    ) : null}
                    {order.invoice?.billingEmail ? (
                      <div className="muted">{order.invoice.billingEmail}</div>
                    ) : null}
                  </td>
                  <td>
                    <div>{formatOptionalFrenchDate(order.createdAt)}</div>
                    <div className="muted">{formatOptionalFrenchDateTime(order.createdAt)}</div>
                  </td>
                  <td>
                    {order.invoice?.number ? (
                      <>
                        <div>{order.invoice.number}</div>
                        <div className="muted">{formatInvoiceStatusFr(order.invoice.status)}</div>
                      </>
                    ) : (
                      <span className="text-xs text-stone-500">Aucune</span>
                    )}
                  </td>
                  <td>
                    <div className="font-medium text-brand-900">{getPaymentLabel(order)}</div>
                    {order.payment?.stripePaymentStatus ? (
                      <div className="muted">
                        Stripe: {order.payment.stripePaymentStatusLabel ?? formatStripePaymentStatusFr(order.payment.stripePaymentStatus)}
                      </div>
                    ) : null}
                    {order.payment?.lastStripeEventType ? (
                      <div className="muted">
                        {order.payment.lastStripeEventLabel ?? order.payment.lastStripeEventType}
                      </div>
                    ) : null}
                  </td>
                  <td>{formatEuroCents(order.totalPriceCents)}</td>
                  <td>
                    <div className="capitalize">{order.statusLabel ?? formatOrderStatusFr(order.status)}</div>
                    {order.hasIssues && (order.issueReasons?.length ?? 0) > 0 ? (
                      <div className="mt-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        <div className="font-semibold">Anomalies détectées</div>
                        <ul className="mt-1 list-disc pl-4">
                          {order.issueReasons?.map((reason) => (
                            <li key={reason}>{reason}</li>
                          ))}
                        </ul>
                      </div>
                    ) : null}
                  </td>
                  <td>
                    <div className="flex flex-wrap gap-3">
                      {getNextStatuses(order.status).length === 0 ? (
                        <span className="inline-flex items-center text-xs text-stone-500">Statut final</span>
                      ) : (
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                          onClick={() => {
                            const options = getNextStatuses(order.status);
                            if (!options.length) return;
                            setEditing({
                              id: order.id,
                              current: (order.status as OrderStatus) ?? 'pending',
                              next: options[0],
                              options,
                            });
                          }}
                          aria-label={`Modifier le statut de la commande ${order.number}`}
                        >
                          Modifier le statut
                        </button>
                      )}
                      <Link className="inline-flex items-center text-sm font-semibold underline" to={`/admin/orders/${order.id}`}>
                        Détails
                      </Link>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>

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
                    onChange={() => setEditing((current) => (current ? { ...current, next: option } : current))}
                  />{' '}
                  {formatOrderStatusFr(option)}
                </label>
              ))}
            </div>
          ) : (
            <p className="text-sm text-stone-500">Aucun changement possible depuis ce statut.</p>
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
