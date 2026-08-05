import { useEffect, useState } from 'react';
import { Link, Outlet, useLocation } from 'react-router';
import { BarChart3 } from 'lucide-react';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { isAnyPathActive } from '@/shared/lib/routes';
import { AdminNavGroupSection } from './AdminNavGroupSection';
import { adminNavGroups, type AdminNavLink } from './adminNavConfig';
import '@/app/styles/admin.css';

export const AdminLayout = () => {
  const location = useLocation();
  const isLinkActive = (link: AdminNavLink) => {
    if (link.to === '/admin') {
      return location.pathname === '/admin';
    }

    return isAnyPathActive(location.pathname, [link.to, ...(link.match ?? [])]);
  };

  const currentGroup = adminNavGroups.find((group) =>
    group.links.some((link) => isLinkActive(link)),
  );
  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() =>
    Object.fromEntries(adminNavGroups.map((group) => [group.id, group.id === currentGroup?.id])),
  );

  useEffect(() => {
    if (!currentGroup) {
      return;
    }

    setOpenGroups((current) => ({ ...current, [currentGroup.id]: true }));
  }, [currentGroup]);

  const toggleGroup = (groupId: string) => {
    setOpenGroups((current) => ({ ...current, [groupId]: !current[groupId] }));
  };

  return (
    <SiteLayout headerVariant="light">
      <div className="admin-shell">
        <aside className="admin-shell__sidebar" aria-label="Navigation admin">
          <div className="admin-shell__heading">
            <span>Back-office</span>
            <strong>Hociatec</strong>
            <small>{currentGroup?.label ?? 'Administration'}</small>
          </div>
          <Link
            to="/admin"
            className={`admin-shell__home-link${location.pathname === '/admin' ? ' is-active' : ''}`}
          >
            <BarChart3 aria-hidden="true" />
            <span>Tableau de bord</span>
          </Link>
          <nav className="admin-shell__nav">
            {adminNavGroups.map((group) => (
              <AdminNavGroupSection
                key={group.id}
                group={group}
                isCurrent={currentGroup?.id === group.id}
                isOpen={openGroups[group.id] ?? false}
                isLinkActive={isLinkActive}
                onToggle={() => toggleGroup(group.id)}
                onClose={() => setOpenGroups((current) => ({ ...current, [group.id]: false }))}
              />
            ))}
          </nav>
        </aside>
        <main className="admin-shell__content">
          <Outlet />
        </main>
      </div>
    </SiteLayout>
  );
};
