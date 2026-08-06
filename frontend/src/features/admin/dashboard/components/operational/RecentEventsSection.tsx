import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatFrenchDateTime } from '@/shared/lib/formatters';
import { DashboardCardAction } from './AdminDashboardShared';

export const RecentEventsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => {
  const eventsPagination = useAdminPagination(dashboard.recentEvents);

  return (
  <section className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
    <div className="mb-4">
      <h3 className="text-lg font-semibold text-white">Journal récent</h3>
      <p className="text-sm text-stone-400">Derniers événements enregistrés sur les commandes.</p>
    </div>
    <div className="space-y-3">
      {eventsPagination.paginatedItems.map((event) => (
        <article key={event.id} className="rounded-2xl bg-brand-900/40 p-4">
          <div className="text-sm font-semibold text-white">{event.order.number}</div>
          <div className="mt-1 text-sm text-stone-500">{event.message || event.type}</div>
          <div className="mt-1 text-xs text-stone-400">
            {formatFrenchDateTime(event.createdAt)}
            {event.actor?.name ? ` · ${event.actor.name}` : ''}
          </div>
          <DashboardCardAction
            to={`/admin/orders/${event.order.id}`}
            label="Voir la commande"
            className="mt-3"
          />
        </article>
      ))}
    </div>
    <PaginationControls
      className="mt-6 text-stone-300"
      page={eventsPagination.page}
      total={eventsPagination.total}
      totalLabel="événement"
      totalPages={eventsPagination.totalPages}
      onPageChange={eventsPagination.setPage}
    />
  </section>
  );
};
