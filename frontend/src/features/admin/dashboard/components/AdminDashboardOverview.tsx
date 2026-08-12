import type { AdminDashboardDto } from '@/features/admin/customers/api';
import { formatEuroCents } from '@/shared/lib/formatters';
import { AdminDashboardPriorityLink } from './AdminDashboardPriorityLink';

type AdminDashboardOverviewProps = {
  dashboard: AdminDashboardDto;
};

const cardClass = 'rounded-2xl border border-brand-700 bg-brand-800/50 p-5';

const MetricCard = ({ label, value, helper }: { label: string; value: number; helper: string }) => (
  <div className={cardClass}>
    <div className="text-sm text-stone-400">{label}</div>
    <div className="mt-2 text-3xl font-semibold text-white">{value}</div>
    <div className="mt-1 text-sm text-stone-500">{helper}</div>
  </div>
);

export const AdminDashboardOverview = ({ dashboard }: AdminDashboardOverviewProps) => (
  <>
    <section className="space-y-4 admin-dashboard__priority-section">
      <div className="admin-dashboard__section-heading">
        <div>
          <h3 className="text-lg font-semibold text-white">Actions prioritaires</h3>
          <p className="text-sm text-stone-400">Les points qui demandent une action immédiate.</p>
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <AdminDashboardPriorityLink
          to="/admin/orders?status=pending"
          label="Commandes à traiter"
          value={dashboard.metrics.statusCounts.pending ?? 0}
          helper="Ouvrir la liste"
        />
        <AdminDashboardPriorityLink
          to="/admin/orders?health=issues"
          label="Incidents de traitement"
          value={dashboard.metrics.issuesCount}
          helper="Traiter maintenant"
        />
        <AdminDashboardPriorityLink
          to="/admin/payments?status=failed"
          label="Paiements échoués"
          value={dashboard.payments.statusCounts.failed ?? 0}
          helper="Analyser les refus"
        />
        <AdminDashboardPriorityLink
          to="/admin/catalog/products?stock=low"
          label="Stocks faibles"
          value={dashboard.metrics.lowStockCount}
          helper="Voir les produits"
        />
        <AdminDashboardPriorityLink
          to="/admin/customers/support"
          label="SAV ouverts"
          value={dashboard.metrics.supportOpenCount ?? 0}
          helper="Ouvrir le SAV"
        />
        <AdminDashboardPriorityLink
          to="/admin/customers/refunds"
          label="Remboursements"
          value={dashboard.metrics.refundsPendingCount ?? 0}
          helper="Traiter les demandes"
        />
      </div>
    </section>

    <section className="space-y-4">
      <div className="admin-dashboard__section-heading">
        <div>
          <h3 className="text-lg font-semibold text-white">Vue d’ensemble</h3>
          <p className="text-sm text-stone-400">Volumes, chiffre d’affaires et base clients.</p>
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          label="Aujourd’hui"
          value={dashboard.metrics.today.count}
          helper={formatEuroCents(dashboard.metrics.today.totalCents)}
        />
        <MetricCard
          label="Cette semaine"
          value={dashboard.metrics.week.count}
          helper={formatEuroCents(dashboard.metrics.week.totalCents)}
        />
        <MetricCard
          label="Ce mois"
          value={dashboard.metrics.month.count}
          helper={formatEuroCents(dashboard.metrics.month.totalCents)}
        />
        <MetricCard
          label="Base clients"
          value={dashboard.metrics.customersCount}
          helper={`${dashboard.topCustomers.length} clients mis en avant`}
        />
      </div>
    </section>
  </>
);
