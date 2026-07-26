import { AdminDashboardHome } from '@/features/admin/dashboard/components/AdminDashboardHome';
import { AdminQuickActions } from '@/features/admin/dashboard/components/AdminQuickActions';
import { useAdminDashboard } from '@/features/admin/dashboard/hooks/useAdminDashboard';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const AdminDashboardPage = () => {
  useDocumentTitle('Administration');
  const { dashboard, error, status } = useAdminDashboard();
  const pendingCount = dashboard?.metrics.statusCounts.pending ?? 0;

  return (
    <section className="admin-dashboard">
      <header className="admin-dashboard__header"><div><p className="admin-dashboard__eyebrow">Admin Hociatec</p><h1>Tableau de bord</h1><p>Une vue simple pour traiter les priorités et rejoindre les bons espaces.</p></div><div className="admin-dashboard__summary" aria-label="Commandes à traiter"><span>À traiter</span><strong>{status === 'success' ? pendingCount : '...'}</strong></div></header>
      <AdminQuickActions />
      <AdminDashboardHome dashboard={dashboard} dashboardError={error} dashboardStatus={status} />
    </section>
  );
};
