import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { AdminDashboardNotifications } from './AdminDashboardNotifications';
import { AdminDashboardOverview } from './AdminDashboardOverview';
import { AdminDashboardOperationalSections } from './AdminDashboardOperationalSections';
import { AdminPaymentsSummary } from './AdminPaymentsSummary';

type AdminDashboardHomeProps = {
  dashboard: AdminDashboardDto | null;
  dashboardError: string | null;
  dashboardStatus: 'loading' | 'error' | 'success';
};

export const AdminDashboardHome = ({
  dashboard,
  dashboardError,
  dashboardStatus,
}: AdminDashboardHomeProps) => (
  <div className="admin-dashboard__live">
    {dashboardStatus === 'loading' && (
      <div
        className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6 text-sm text-stone-500"
        aria-hidden="true"
      >
        Chargement des indicateurs...
      </div>
    )}

    {dashboardError && (
      <div className="rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
        {dashboardError}
      </div>
    )}

    {dashboard && (
      <>
        <AdminDashboardOverview dashboard={dashboard} />

        <AdminDashboardNotifications dashboard={dashboard} />
        <AdminPaymentsSummary dashboard={dashboard} />
        <AdminDashboardOperationalSections dashboard={dashboard} />
      </>
    )}
  </div>
);
