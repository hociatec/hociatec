import { useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { useLocation, useNavigate, Link } from 'react-router-dom';
import {
  BriefcaseBusiness,
  CalendarDays,
  ClipboardCheck,
  FileText,
  GraduationCap,
  KeyRound,
  MonitorCog,
  ShoppingBag,
} from 'lucide-react';

import { isPathActive } from '@/shared/lib/routes';

const primaryLinks = [
  { path: '/catalogue/vente', label: 'Vente', Icon: ShoppingBag },
  { path: '/catalogue/location', label: 'Location', Icon: KeyRound },
  { path: '/services', label: 'Services', Icon: MonitorCog },
  { path: '/formations', label: 'Formations', Icon: GraduationCap },
] as const;

const serviceLinks = [
  { path: '/appointments/book', label: 'Prendre rendez-vous', Icon: CalendarDays },
  { path: '/devis/nouveau', label: 'Créer un devis', Icon: FileText },
  { path: '/audits/request', label: 'Demander un audit', Icon: ClipboardCheck },
] as const;

export const SiteHeaderNavigation = () => {
  const { pathname } = useLocation();
  const navigate = useNavigate();
  const [servicesOpen, setServicesOpen] = useState(false);
  const servicesSummaryRef = useRef<HTMLElement | null>(null);

  const closeServicesMenu = () => {
    setServicesOpen(false);
    servicesSummaryRef.current?.focus();
  };

  const handleServicesKeyDown = (event: KeyboardEvent<HTMLDetailsElement>) => {
    if (event.key !== 'Escape') return;
    event.preventDefault();
    event.stopPropagation();
    closeServicesMenu();
  };

  return (
    <nav className="site-header__nav" aria-label="Navigation principale">
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
      <details
        className="site-header__service-menu"
        open={servicesOpen}
        onToggle={(event) => setServicesOpen(event.currentTarget.open)}
        onKeyDown={handleServicesKeyDown}
      >
        <summary ref={servicesSummaryRef} className="site-header__service-trigger">
          <BriefcaseBusiness aria-hidden="true" />
          <span>Prestations</span>
        </summary>
        <div className="site-header__service-panel">
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
    </nav>
  );
};
