import { useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { useLocation, useNavigate, Link } from 'react-router';
import {
  BriefcaseBusiness,
  CalendarDays,
  ClipboardCheck,
  FileText,
  GraduationCap,
  MonitorCog,
  PackageSearch,
  RefreshCw,
  ShoppingBag,
} from 'lucide-react';

import { isAnyPathActive, isPathActive } from '@/shared/lib/routes';

const primaryLinks = [
  { path: '/formations', label: 'Formations', Icon: GraduationCap },
  { path: '/contact', label: 'Contact', Icon: BriefcaseBusiness },
] as const;

const catalogLinks = [
  { path: '/catalogue/vente', label: 'Vente', Icon: ShoppingBag },
  { path: '/catalogue/location', label: 'Location', Icon: PackageSearch },
  { path: '/reprise', label: 'Reprise', Icon: RefreshCw },
] as const;

const serviceLinks = [
  { path: '/services', label: 'Nos services', Icon: MonitorCog },
  { path: '/appointments/book', label: 'Prendre rendez-vous', Icon: CalendarDays },
  { path: '/devis/nouveau', label: 'Créer un devis', Icon: FileText },
  { path: '/audits/request', label: 'Demander un audit', Icon: ClipboardCheck },
] as const;

export const SiteHeaderNavigation = () => {
  const { pathname } = useLocation();
  const navigate = useNavigate();
  const [catalogOpen, setCatalogOpen] = useState(false);
  const [servicesOpen, setServicesOpen] = useState(false);
  const catalogSummaryRef = useRef<HTMLElement | null>(null);
  const servicesSummaryRef = useRef<HTMLElement | null>(null);

  const closeCatalogMenu = () => {
    setCatalogOpen(false);
    catalogSummaryRef.current?.focus();
  };

  const closeServicesMenu = () => {
    setServicesOpen(false);
    servicesSummaryRef.current?.focus();
  };

  const handleCatalogKeyDown = (event: KeyboardEvent<HTMLDetailsElement>) => {
    if (event.key !== 'Escape') return;
    event.preventDefault();
    event.stopPropagation();
    closeCatalogMenu();
  };

  const handleServicesKeyDown = (event: KeyboardEvent<HTMLDetailsElement>) => {
    if (event.key !== 'Escape') return;
    event.preventDefault();
    event.stopPropagation();
    closeServicesMenu();
  };

  return (
    <nav className="site-header__nav" aria-label="Navigation principale">
      <details
        className="site-header__menu"
        open={catalogOpen}
        onToggle={(event) => setCatalogOpen(event.currentTarget.open)}
        onKeyDown={handleCatalogKeyDown}
      >
        <summary
          ref={catalogSummaryRef}
          className={`site-header__menu-trigger${isAnyPathActive(pathname, ['/catalogue', '/reprise']) ? ' site-header__link--active' : ''}`}
        >
          <ShoppingBag aria-hidden="true" />
          <span>Catalogue</span>
        </summary>
        <div className="site-header__menu-panel">
          {catalogLinks.map(({ path, label, Icon }) => (
            <button
              key={path}
              type="button"
              onClick={() => {
                setCatalogOpen(false);
                navigate(path);
              }}
            >
              <Icon aria-hidden="true" />
              <span>{label}</span>
            </button>
          ))}
        </div>
      </details>
      <details
        className="site-header__menu"
        open={servicesOpen}
        onToggle={(event) => setServicesOpen(event.currentTarget.open)}
        onKeyDown={handleServicesKeyDown}
      >
        <summary
          ref={servicesSummaryRef}
          className={`site-header__menu-trigger${isAnyPathActive(pathname, ['/services', '/appointments/book', '/devis/nouveau', '/audits/request']) ? ' site-header__link--active' : ''}`}
        >
          <MonitorCog aria-hidden="true" />
          <span>Services</span>
        </summary>
        <div className="site-header__menu-panel">
          {serviceLinks.map(({ path, label, Icon }) => (
            <button
              key={path}
              type="button"
              onClick={() => {
                setServicesOpen(false);
                navigate(path);
              }}
            >
              <Icon aria-hidden="true" />
              <span>{label}</span>
            </button>
          ))}
        </div>
      </details>
      {primaryLinks.map(({ path, label, Icon }) => (
        <Link
          key={path}
          to={path}
          className={`site-header__link${isPathActive(pathname, path) ? ' site-header__link--active' : ''}`}
        >
          <Icon aria-hidden="true" />
          {label}
        </Link>
      ))}
    </nav>
  );
};
