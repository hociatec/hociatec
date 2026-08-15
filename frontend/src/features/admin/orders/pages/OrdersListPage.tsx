import { useCallback, useMemo } from 'react';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableSkeleton } from '@/shared/components/admin/AdminDataView';
import { AdminOrdersTable } from '@/features/admin/orders/components/AdminOrdersTable';
import { OrderStatusDialog } from '@/features/admin/orders/components/OrderStatusDialog';
import { useAdminOrdersList } from '../hooks/useAdminOrdersList';
import { type OrderSortKey } from '../lib/adminOrderList';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

export const OrdersListPage = () => {
  const {
    pagination,
    setPage,
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
    orders,
    handleConfirmUpdate,
    handleEditStatus,
    selectedOrderIds,
    bulkStatus,
    setBulkStatus,
    bulkMessage,
    bulkError,
    bulkUpdating,
    toggleSelectedOrder,
    toggleVisibleOrders,
    submitBulkUpdate,
  } = useAdminOrdersList();

  const statusOptionsItems = useMemo(
    () => [{ value: 'all', label: 'Tous les statuts' }, ...statusOptions],
    [statusOptions],
  );
  const sortOptions = useMemo(
    () => [
      { value: 'newest', label: 'Plus récentes' },
      { value: 'oldest', label: 'Plus anciennes' },
      { value: 'amount_desc', label: 'Montant décroissant' },
      { value: 'amount_asc', label: 'Montant croissant' },
      { value: 'customer_asc', label: 'Client A → Z' },
    ],
    [],
  );
  const healthOptions = useMemo(
    () => [
      { value: 'all', label: 'Toutes' },
      { value: 'issues', label: 'Avec incident de traitement' },
    ],
    [],
  );

  const onSearchChange = useCallback((value: string) => setSearch(value), [setSearch]);
  const onFilterChange = useCallback(
    (value: string) => setFilter(value as typeof filter),
    [setFilter],
  );
  const onSortChange = useCallback((value: string) => setSort(value as OrderSortKey), [setSort]);
  const onHealthChange = useCallback(
    (value: string) => setHealth(value as typeof health),
    [setHealth],
  );

  return (
    <PageContainer size="admin" title="Commandes">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {pagination.total} commande{pagination.total > 1 ? 's' : ''} trouvée
          {pagination.total > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
          Recherchez une commande par numéro, client, email, société ou facture, puis triez la liste
          selon votre besoin.
        </p>
      </div>

      <FilterBar>
        <SearchFilter value={search} onChange={onSearchChange} placeholder="Rechercher un client, une commande, un email, une facture..." />
        <SelectFilter value={filter} onChange={onFilterChange} options={statusOptionsItems} ariaLabel="Filtre statut" />
        <SelectFilter value={sort} onChange={onSortChange} options={sortOptions} ariaLabel="Tri commandes" />
        <SelectFilter value={health} onChange={onHealthChange} options={healthOptions} ariaLabel="Filtre incident" />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {bulkError && <FeedbackMessage>{bulkError}</FeedbackMessage>}
      {bulkMessage && <FeedbackMessage variant="success">{bulkMessage}</FeedbackMessage>}

      <section className="mb-6 rounded-3xl border border-stone-200 bg-white p-4">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div className="space-y-1">
            <h2 className="text-base font-semibold text-stone-900">Statut en masse</h2>
            <p className="text-sm text-stone-600">
              {selectedOrderIds.length} commande{selectedOrderIds.length > 1 ? 's' : ''} sélectionnée{selectedOrderIds.length > 1 ? 's' : ''} sur cette page.
            </p>
          </div>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label className="flex flex-col gap-1 text-sm text-stone-700">
              <span>Nouveau statut</span>
              <select
                className="rounded-xl border border-stone-300 px-3 py-2"
                value={bulkStatus}
                onChange={(event) => setBulkStatus(event.target.value as typeof bulkStatus)}
              >
                {statusOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>
            <button
              type="button"
              className="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
              onClick={toggleVisibleOrders}
              disabled={orders.length === 0}
            >
              {orders.length > 0 && orders.every((order) => selectedOrderIds.includes(order.id))
                ? 'Tout désélectionner'
                : 'Sélectionner la page'}
            </button>
            <button
              type="button"
              className="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
              onClick={submitBulkUpdate}
              disabled={selectedOrderIds.length === 0 || bulkUpdating}
            >
              {bulkUpdating ? 'Mise à jour...' : 'Appliquer aux commandes sélectionnées'}
            </button>
          </div>
        </div>
      </section>

      <AdminListState
        loading={status === 'loading'}
        isEmpty={status === 'success' && orders.length === 0}
        loadingLabel="Chargement..."
        loadingSkeleton={<AdminTableSkeleton columns={8} rows={12} />}
        emptyLabel="Aucune commande ne correspond aux filtres actuels."
      >
        <AdminOrdersTable
          orders={orders}
          onEditStatus={handleEditStatus}
          selectedOrderIds={selectedOrderIds}
          onToggleOrderSelection={toggleSelectedOrder}
          onToggleVisibleOrders={toggleVisibleOrders}
        />
      </AdminListState>

      <PaginationControls
        page={pagination.page}
        total={pagination.total}
        totalLabel="commande"
        totalPages={pagination.totalPages}
        onPageChange={setPage}
      />

      <OrderStatusDialog
        editing={editing}
        setEditing={setEditing}
        updateError={updateError}
        setUpdateError={setUpdateError}
        onConfirm={handleConfirmUpdate}
      />
    </PageContainer>
  );
};
