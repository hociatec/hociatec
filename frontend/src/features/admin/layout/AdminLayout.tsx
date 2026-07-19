import { Link, Outlet, useLocation } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';

const adminLinks = [
  { to: '/admin', label: 'Vue globale' },
  { to: '/admin/orders', label: 'Commandes' },
  { to: '/admin/catalog/products', label: 'Catalogue' },
  { to: '/admin/customers', label: 'Clients' },
  { to: '/admin/appointments/prestations', label: 'Rendez-vous' },
  { to: '/admin/quotes', label: 'Devis' },
  { to: '/admin/marketing', label: 'Marketing' },
  { to: '/admin/operations', label: 'Exploitation' },
];

export const AdminLayout = () => {
  const location = useLocation();

  return (
    <SiteLayout headerVariant="light">
      <div className="admin-shell">
        <aside className="admin-shell__sidebar" aria-label="Navigation admin">
          <div className="admin-shell__heading">
            <span>Back-office</span>
            <strong>Hociatec</strong>
          </div>
          <nav className="admin-shell__nav">
            {adminLinks.map((link) => {
              const active =
                link.to === '/admin'
                  ? location.pathname === '/admin'
                  : location.pathname === link.to || location.pathname.startsWith(`${link.to}/`);

              return (
                <Link key={link.to} to={link.to} className={active ? 'is-active' : undefined}>
                  {link.label}
                </Link>
              );
            })}
          </nav>
        </aside>
        <main className="admin-shell__content">
          <Outlet />
        </main>
      </div>
    </SiteLayout>
  );
};
