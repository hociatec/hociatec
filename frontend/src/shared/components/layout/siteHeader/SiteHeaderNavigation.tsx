import { useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { useLocation, Link } from 'react-router';
import {
  BriefcaseBusiness,
  CalendarDays,
  ClipboardCheck,
  Download,
  FileText,
  GraduationCap,
  MonitorCog,
  PackageSearch,
  RefreshCw,
  ShoppingBag,
} from 'lucide-react';

import { isAnyPathActive, isPathActive } from '@/shared/lib/routes';

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

const iosIpaDownloadPath =
  'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-latest.ipa';
const altStoreSourcePath =
  'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json';
const altStoreAddSourcePath =
  `altstore://source?url=${encodeURIComponent(altStoreSourcePath)}`;

export const SiteHeaderNavigation = () => {
  const { pathname } = useLocation();
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
            <Link
              key={path}
              to={path}
              prefetch="intent"
              className={isPathActive(pathname, path) ? 'is-active' : undefined}
              onClick={() => setCatalogOpen(false)}
            >
              <Icon aria-hidden="true" />
              <span>{label}</span>
            </Link>
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
          <span>Prestations</span>
        </summary>
        <div className="site-header__menu-panel">
          {serviceLinks.map(({ path, label, Icon }) => (
            <Link
              key={path}
              to={path}
              prefetch="intent"
              className={isPathActive(pathname, path) ? 'is-active' : undefined}
              onClick={() => setServicesOpen(false)}
            >
              <Icon aria-hidden="true" />
              <span>{label}</span>
            </Link>
          ))}
        </div>
      </details>

      <Link
        to="/formations"
        prefetch="viewport"
        className={`site-header__link${isPathActive(pathname, '/formations') ? ' site-header__link--active' : ''}`}
      >
        <GraduationCap aria-hidden="true" />
        Formations
      </Link>

      <Link
        to="/contact"
        prefetch="intent"
        className={`site-header__link site-header__link--contact${isPathActive(pathname, '/contact') ? ' site-header__link--contact-active' : ''}`}
      >
        <BriefcaseBusiness aria-hidden="true" />
        Contact
      </Link>

      <a
        href={altStoreAddSourcePath}
        className="site-header__cta"
        rel="noopener noreferrer"
        target="_blank"
      >
        <Download aria-hidden="true" />
        Ajouter a AltStore
      </a>

      <a
        href={iosIpaDownloadPath}
        download="hociatec-altstore-latest.ipa"
        className="site-header__cta"
        rel="noopener noreferrer"
        target="_blank"
      >
        <Download aria-hidden="true" />
        Télécharger l&apos;app iPhone
      </a>
    </nav>
  );
};
