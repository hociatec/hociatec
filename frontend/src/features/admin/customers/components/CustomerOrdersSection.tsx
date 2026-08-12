import { Link } from 'react-router';

import type { OrderDto } from '@/features/orders/publicApi';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { type OrderFilter } from './customerDetailShared';
import type { AdminCustomerOrdersStatsDto } from '@/features/admin/customers/api';
import type { PaginationMeta } from '@/shared/types/api';

export const CustomerOrdersSection = ({
  orders,
  ordersMeta,
  orderStats,
  orderFilter,
  onOrderPageChange,
  onOrderFilterChange,
}: {
  orders: OrderDto[];
  ordersMeta: PaginationMeta;
  orderStats: AdminCustomerOrdersStatsDto;
  orderFilter: OrderFilter;
  onOrderPageChange: (updater: (page: number) => number) => void;
  onOrderFilterChange: (filter: OrderFilter) => void;
}) => {
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
            Toutes ({orderStats.all})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'open' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('open')}
          >
            En cours ({orderStats.open})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'delivered' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('delivered')}
          >
            Livrées ({orderStats.delivered})
          </button>
          <button
            type="button"
            className={`rounded-full px-3 py-1.5 ${orderFilter === 'cancelled' ? 'bg-brand-900 text-white' : 'bg-brand-50 text-stone-700'}`}
            onClick={() => onOrderFilterChange('cancelled')}
          >
            Annulées ({orderStats.cancelled})
          </button>
        </div>
      </div>
      {ordersMeta.total === 0 ? (
        <p className="text-sm text-stone-500">Aucune commande pour ce client.</p>
      ) : (
        <div className="space-y-3">
          {orders.map((order) => (
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
          <PaginationControls
            page={ordersMeta.page}
            total={ordersMeta.total}
            totalLabel="commande"
            totalPages={ordersMeta.totalPages}
            onPageChange={onOrderPageChange}
          />
        </div>
      )}
    </section>
  );
};
