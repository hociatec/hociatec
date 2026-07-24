import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarDays, FileText, Mail, ShoppingCart } from 'lucide-react';

import { fetchAdminDashboard, type AdminDashboardDto } from '@/features/admin/customers/api';
import { AdminDashboardHome } from '@/features/admin/extracted/dashboard/AdminDashboardHome';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const quickActions = [
  { to: '/admin/orders', label: 'Traiter les commandes', icon: ShoppingCart },
  { to: '/admin/quotes/new', label: 'Créer un devis', icon: FileText },
  { to: '/admin/appointments/schedule', label: 'Planning RDV', icon: CalendarDays },
  { to: '/admin/marketing', label: 'Envoyer une campagne', icon: Mail },
];

export const AdminDashboardPage = () => {
  useDocumentTitle('Administration');
  const [dashboard, setDashboard] = useState<AdminDashboardDto | null>(null);
  const [dashboardStatus, setDashboardStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [dashboardError, setDashboardError] = useState<string | null>(null);

  useEffect(() => {
    setDashboardStatus('loading');
    setDashboardError(null);
    void fetchAdminDashboard()
      .then((data) => {
        setDashboard(data);
        setDashboardStatus('success');
      })
      .catch((error: unknown) => {
        setDashboardStatus('error');
        setDashboardError(error instanceof Error ? error.message : "Les indicateurs d'administration n'ont pas pu être chargés.");
      });
  }, []);

  const pendingCount = useMemo(() => dashboard?.metrics.statusCounts.pending ?? 0, [dashboard]);

  return (
    <section className="admin-dashboard">
      <header className="admin-dashboard__header">
        <div>
          <p className="admin-dashboard__eyebrow">Admin Hociatec</p>
          <h1>Tableau de bord</h1>
          <p>
            Une vue simple pour traiter les priorités et rejoindre les bons espaces.
          </p>
        </div>
        <div className="admin-dashboard__summary" aria-label="Commandes à traiter">
          <span>À traiter</span>
          <strong>{dashboardStatus === 'success' ? pendingCount : '...'}</strong>
        </div>
      </header>

      <section className="admin-dashboard__quick-actions" aria-label="Actions rapides">
        {quickActions.map((action) => {
          const Icon = action.icon;

          return (
            <article key={action.to}>
              <Icon aria-hidden="true" />
              <span>{action.label}</span>
              <Link to={action.to}>Ouvrir</Link>
            </article>
          );
        })}
      </section>

      <AdminDashboardHome
        dashboard={dashboard}
        dashboardError={dashboardError}
        dashboardStatus={dashboardStatus}
      />
    </section>
  );
};
