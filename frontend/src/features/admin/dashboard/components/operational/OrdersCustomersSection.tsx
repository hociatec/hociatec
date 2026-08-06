import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { DashboardCardAction, PanelTitle } from './AdminDashboardShared';

export const OrdersCustomersSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => {
  const ordersPagination = useAdminPagination(dashboard.recentOrders);
  const customersPagination = useAdminPagination(dashboard.topCustomers);

  return (
  <section className="grid gap-6 xl:grid-cols-3">
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6 xl:col-span-2">
      <PanelTitle
        title="Suivi commandes"
        helper="Dernières commandes enregistrées."
        to="/admin/orders"
        linkLabel="Toutes les commandes"
      />
      <div className="space-y-3">
        {ordersPagination.paginatedItems.map((order) => (
          <article
            key={order.id}
            className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <div className="font-semibold text-white">{order.number}</div>
              <div className="text-sm text-stone-500">
                {order.customerDisplayName} · {formatFrenchDateTime(order.createdAt)}
              </div>
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <div className="text-sm font-semibold text-white">
                {formatEuroCents(order.totalPriceCents)}
              </div>
              <div className="text-xs uppercase tracking-wide text-stone-400">
                {order.statusLabel}
              </div>
              <DashboardCardAction to={`/admin/orders/${order.id}`} label="Voir" />
            </div>
          </article>
        ))}
      </div>
      <PaginationControls
        className="mt-6 text-stone-300"
        page={ordersPagination.page}
        total={ordersPagination.total}
        totalLabel="commande"
        totalPages={ordersPagination.totalPages}
        onPageChange={ordersPagination.setPage}
      />
    </div>
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
      <PanelTitle
        title="Clients à suivre"
        helper="Top clients par valeur."
        to="/admin/customers"
        linkLabel="Tous les clients"
      />
      <div className="space-y-3">
        {customersPagination.paginatedItems.map((customer) => (
          <article key={customer.id} className="rounded-2xl bg-brand-900/40 p-4">
            <div className="font-semibold text-white">
              {customer.firstName} {customer.lastName}
            </div>
            <div className="text-sm text-stone-500">{customer.email}</div>
            <div className="mt-2 text-sm text-stone-200">
              {customer.ordersCount} commande{customer.ordersCount > 1 ? 's' : ''} ·{' '}
              {formatEuroCents(customer.totalSpentCents)}
            </div>
            <DashboardCardAction
              to={`/admin/customers/${customer.id}`}
              label="Ouvrir"
              className="mt-3"
            />
          </article>
        ))}
      </div>
      <PaginationControls
        className="mt-6 text-stone-300"
        page={customersPagination.page}
        total={customersPagination.total}
        totalLabel="client"
        totalPages={customersPagination.totalPages}
        onPageChange={customersPagination.setPage}
      />
    </div>
  </section>
  );
};
