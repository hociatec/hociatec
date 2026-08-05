import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState } from '@/shared/components/admin/AdminDataView';
import { AdminOrdersTable } from '@/features/admin/orders/components/AdminOrdersTable';
import { OrderStatusDialog } from '@/features/admin/orders/components/OrderStatusDialog';
import type { OrderDto } from '../../../orders/api';
import { useAdminOrdersList } from '../hooks/useAdminOrdersList';
import { type OrderSortKey, type OrderStatus } from '../lib/adminOrderList';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';

export const OrdersListPage = () => {
  const {
    orders,
    status,
    error,
    filter,
    setFilter,
    statusOptions,
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
            ...statusOptions,
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
        <AdminOrdersTable
          orders={filteredOrders}
          onEditStatus={(order: OrderDto, options) => {
            const nextStatus = options[0];
            if (!nextStatus) return;
            setEditing({
              id: order.id,
              current: (order.status as OrderStatus) ?? 'pending',
              next: nextStatus,
              options,
              order,
            });
          }}
        />
      </AdminListState>

      <OrderStatusDialog editing={editing} setEditing={setEditing} updateError={updateError} setUpdateError={setUpdateError} onConfirm={handleConfirmUpdate} />
    </PageContainer>
  );
};
