import { Link } from 'react-router-dom';

import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { OrderStatusDialog } from '@/features/admin/orders/components/OrderStatusDialog';
import {
  formatInvoiceStatusFr,
  formatOrderStatusFr,
  formatStripePaymentStatusFr,
} from '../../../orders/api';
import { useAdminOrdersList } from '../hooks/useAdminOrdersList';
import {
  getNextOrderStatuses,
  getOrderCustomerLabel,
  getOrderPaymentLabel,
  type OrderSortKey,
  type OrderStatus,
} from '../lib/adminOrderList';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import {
  formatEuroCents,
  formatOptionalFrenchDate,
  formatOptionalFrenchDateTime,
} from '@/shared/lib/formatters';

export const OrdersListPage = () => {
  const {
    orders,
    status,
    error,
    filter,
    setFilter,
    health,
    setHealth,
    search,
    setSearch,
    sort,
    setSort,
    editing,
    setEditing,
    updateError,
    setUpdateError,
    filteredOrders,
    handleConfirmUpdate,
  } = useAdminOrdersList();

  return (
    <PageContainer size="admin" title="Commandes">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filteredOrders.length} commande{filteredOrders.length > 1 ? 's' : ''} affichée
          {filteredOrders.length > 1 ? 's' : ''} sur {orders.length}.
        </p>
        <p className="text-sm text-stone-500">
          Recherchez une commande par numéro, client, email, société ou facture, puis triez la liste
          selon votre besoin.
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
          onChange={(v) => setSort(v as OrderSortKey)}
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
                    <div className="font-medium text-brand-900">{getOrderPaymentLabel(order)}</div>
                    {order.payment?.stripePaymentStatus ? (
                      <div className="muted">
                        Stripe:{' '}
                        {order.payment.stripePaymentStatusLabel ??
                          formatStripePaymentStatusFr(order.payment.stripePaymentStatus)}
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
                    <div className="capitalize">
                      {order.statusLabel ?? formatOrderStatusFr(order.status)}
                    </div>
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
                      {getNextOrderStatuses(order.status).length === 0 ? (
                        <span className="inline-flex items-center text-xs text-stone-500">
                          Statut final
                        </span>
                      ) : (
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                          onClick={() => {
                            const options = getNextOrderStatuses(order.status);
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
                      <Link
                        className="inline-flex items-center text-sm font-semibold underline"
                        to={`/admin/orders/${order.id}`}
                      >
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

      <OrderStatusDialog editing={editing} setEditing={setEditing} updateError={updateError} setUpdateError={setUpdateError} onConfirm={handleConfirmUpdate} />
    </PageContainer>
  );
};
