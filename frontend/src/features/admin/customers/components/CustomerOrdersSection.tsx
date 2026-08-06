import { Link } from 'react-router';

import type { OrderDto } from '@/features/orders/publicApi';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { type OrderFilter } from './customerDetailShared';

export const CustomerOrdersSection = ({
  filteredOrders,
  orderFilter,
  orders,
  onOrderFilterChange,
}: {
  filteredOrders: OrderDto[];
  orderFilter: OrderFilter;
  orders: OrderDto[];
  onOrderFilterChange: (filter: OrderFilter) => void;
}) => {
  const ordersPagination = useAdminPagination(filteredOrders, orderFilter);
  const openOrdersCount = orders.filter(
    (order) => order.status === 'pending' || order.status === 'confirmed',
  ).length;
  const deliveredOrdersCount = orders.filter((order) => order.status === 'delivered').length;
  const cancelledOrdersCount = orders.filter((order) => order.status === 'cancelled').length;

  return (
    <section className="rounded-2xl border border-brand-100 p-4">
      <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 className="font-semibold">Commandes</h2>
        <div className="flex flex-wrap gap-2 text-sm">
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'all' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('all')}
          >
            Toutes ({orders.length})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'open' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('open')}
          >
            En cours ({openOrdersCount})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'delivered' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('delivered')}
          >
            Livrées ({deliveredOrdersCount})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'cancelled' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('cancelled')}
          >
            Annulées ({cancelledOrdersCount})
          </button>
        </div>
      </div>
      {orders.length === 0 ? (
        <p className="text-sm text-stone-500">Aucune commande pour ce client.</p>
      ) : (
        <div className="space-y-3">
          {ordersPagination.paginatedItems.map((order) => (
            <div
              key={order.id}
              className="flex flex-col gap-3 rounded-2xl bg-brand-50 p-4 md:flex-row md:items-center md:justify-between"
            >
              <div>
                <div className="font-semibold text-brand-900">{order.number}</div>
                <div className="text-sm text-stone-600">
                  {formatFrenchDateTime(order.createdAt)} ·{' '}
                  {order.statusLabel}
                </div>
                {order.invoice?.number ? (
                  <div className="text-sm text-stone-500">Facture {order.invoice.number}</div>
                ) : null}
              </div>
              <div className="flex items-center gap-4">
                <div className="text-sm font-semibold text-brand-900">
                  {formatEuroCents(order.totalPriceCents)}
                </div>
                <Link className="underline text-sm" to={`/admin/orders/${order.id}`}>
                  Voir la commande
                </Link>
              </div>
            </div>
          ))}
          {filteredOrders.length === 0 ? (
            <p className="text-sm text-stone-500">Aucune commande dans ce filtre.</p>
          ) : null}
          <PaginationControls
            page={ordersPagination.page}
            total={ordersPagination.total}
            totalLabel="commande"
            totalPages={ordersPagination.totalPages}
            onPageChange={ordersPagination.setPage}
          />
        </div>
      )}
    </section>
  );
};
