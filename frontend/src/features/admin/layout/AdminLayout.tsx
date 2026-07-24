import { useEffect, useState } from 'react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import {
  BarChart3,
  ChevronDown,
  Mail,
  Package,
  Settings,
  ShoppingCart,
  Users,
  type LucideIcon,
} from 'lucide-react';

import { SiteLayout } from '@/shared/components/SiteLayout';

type AdminNavLink = {
  to: string;
  label: string;
  match?: string[];
};

type AdminNavGroup = {
  id: string;
  label: string;
  icon: LucideIcon;
  links: AdminNavLink[];
};

const adminNavGroups: AdminNavGroup[] = [
  {
    id: 'sales',
    label: 'Ventes',
    icon: ShoppingCart,
    links: [
      { to: '/admin/orders', label: 'Commandes' },
      { to: '/admin/payments', label: 'Paiements' },
      { to: '/admin/quotes', label: 'Devis' },
      { to: '/admin/services', label: 'Services' },
    ],
  },
  {
    id: 'catalog',
    label: 'Catalogue',
    icon: Package,
    links: [
      { to: '/admin/catalog/products', label: 'Tous les produits' },
      { to: '/admin/catalog/categories', label: 'Catégories' },
      { to: '/admin/catalog/brands', label: 'Marques' },
      { to: '/admin/promotions', label: 'Promotions' },
      { to: '/admin/vouchers', label: 'Bons de réduction' },
    ],
  },
  {
    id: 'customers',
    label: 'Relation client',
    icon: Users,
    links: [
      { to: '/admin/customers', label: 'Liste des clients' },
      { to: '/admin/loyalty', label: 'Fidélité' },
      { to: '/admin/appointments/prestations', label: 'Prestations RDV' },
      { to: '/admin/appointments/schedule', label: 'Planning RDV' },
      { to: '/admin/trainings', label: 'Formations' },
      { to: '/admin/trainings/sessions', label: 'Sessions' },
      { to: '/admin/trainings/enrollments', label: 'Inscriptions' },
      { to: '/admin/audits', label: 'Audits' },
    ],
  },
  {
    id: 'marketing',
    label: 'Marketing',
    icon: Mail,
    links: [
      { to: '/admin/marketing', label: 'Campagnes' },
      { to: '/admin/marketing/templates', label: 'Modèles e-mail' },
    ],
  },
  {
    id: 'system',
    label: 'Système',
    icon: Settings,
    links: [
      { to: '/admin/operations', label: 'Opérations' },
      { to: '/admin/backups', label: 'Sauvegardes et maintenance' },
    ],
  },
];

export const AdminLayout = () => {
  const location = useLocation();
  const isLinkActive = (link: AdminNavLink) => {
    if (link.to === '/admin') {
      return location.pathname === '/admin';
    }

    const paths = [link.to, ...(link.match ?? [])];

    return paths.some((path) => location.pathname === path || location.pathname.startsWith(`${path}/`));
  };

  const currentGroup = adminNavGroups.find((group) => group.links.some((link) => isLinkActive(link)));
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

const AdminNavGroupSection = ({
  group,
  isCurrent,
  isLinkActive,
  isOpen,
  onToggle,
}: {
  group: AdminNavGroup;
  isCurrent: boolean;
  isLinkActive: (link: AdminNavLink) => boolean;
  isOpen: boolean;
  onToggle: () => void;
}) => {
  const Icon = group.icon;

  return (
    <section className={`admin-shell__nav-group${isCurrent ? ' is-current' : ''}`}>
      <button
        type="button"
        className="admin-shell__nav-trigger"
        aria-expanded={isOpen}
        aria-controls={`admin-nav-${group.id}`}
        onClick={onToggle}
      >
        <Icon aria-hidden="true" />
        <strong>{group.label}</strong>
        <ChevronDown aria-hidden="true" className="admin-shell__nav-chevron" />
      </button>
      <div id={`admin-nav-${group.id}`} className="admin-shell__submenu" hidden={!isOpen}>
        {group.links.map((link) => (
          <Link key={link.to} to={link.to} className={isLinkActive(link) ? 'is-active' : undefined}>
            <span>{link.label}</span>
          </Link>
        ))}
      </div>
    </section>
  );
};
