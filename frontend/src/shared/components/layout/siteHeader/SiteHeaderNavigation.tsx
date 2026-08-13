import { useEffect, useRef, useState } from 'react';
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
  { path: '/services', label: 'Services', Icon: MonitorCog },
  { path: '/formations', label: 'Formations', Icon: GraduationCap },
] as const;

const prestationLinks = [
  { path: '/reprise', label: 'Reprise', Icon: RefreshCw },
  { path: '/appointments/book', label: 'Prendre rendez-vous', Icon: CalendarDays },
  { path: '/devis/nouveau', label: 'Créer un devis', Icon: FileText },
  { path: '/audits/request', label: 'Demander un audit', Icon: ClipboardCheck },
] as const;

const altStoreSourcePath = 'https://hociatec.fr/hociatec-altstore-source.json';
const iosDownloadProxyPath = 'https://hociatec.fr/api/public/ios/latest-download';
const sideStoreAddSourcePath = `sidestore://source?url=${encodeURIComponent(altStoreSourcePath)}`;

type AltStoreSourceVersion = {
  version: string;
  buildVersion?: string;
  downloadURL?: string;
};

type AltStoreSourceApp = {
  versions?: AltStoreSourceVersion[];
};

type AltStoreSourcePayload = {
  apps?: AltStoreSourceApp[];
};

type PublishedIosRelease = {
  version: string;
  downloadUrl: string;
};

const fetchPublishedIosRelease = async (): Promise<PublishedIosRelease | null> => {
  const response = await fetch(altStoreSourcePath, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Impossible de charger la version iPhone publiee (${response.status}).`);
  }

  const payload = (await response.json()) as AltStoreSourcePayload;
  const release = payload.apps?.[0]?.versions?.[0];
  const version = release?.version?.trim();
  const downloadUrl = release?.downloadURL?.trim();

  if (!version || version.length === 0 || !downloadUrl || downloadUrl.length === 0) {
    return null;
  }

  return {
    version,
    downloadUrl,
  };
};

export const SiteHeaderNavigation = () => {
  const { pathname } = useLocation();
  const [offerOpen, setOfferOpen] = useState(false);
  const [publishedIosRelease, setPublishedIosRelease] = useState<PublishedIosRelease | null>(null);
  const offerSummaryRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    let cancelled = false;

    void fetchPublishedIosRelease()
      .then((release) => {
        if (!cancelled) {
          setPublishedIosRelease(release);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setPublishedIosRelease(null);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const iosDownloadLabel = publishedIosRelease
    ? `Télécharger l'app iPhone (${publishedIosRelease.version})`
    : "Télécharger l'app iPhone";
  const iosDownloadPath = publishedIosRelease ? iosDownloadProxyPath : '#';

  const closeOfferMenu = () => {
    setOfferOpen(false);
    offerSummaryRef.current?.focus();
  };

  const handleOfferKeyDown = (event: KeyboardEvent<HTMLDetailsElement>) => {
    if (event.key !== 'Escape') return;
    event.preventDefault();
    event.stopPropagation();
    closeOfferMenu();
  };

  return (
    <nav className="site-header__nav" aria-label="Navigation principale">
      <details
        className="site-header__menu"
        open={offerOpen}
        onToggle={(event) => setOfferOpen(event.currentTarget.open)}
        onKeyDown={handleOfferKeyDown}
      >
        <summary
          ref={offerSummaryRef}
          className={`site-header__menu-trigger${isAnyPathActive(pathname, ['/catalogue', '/services', '/formations', '/reprise', '/appointments/book', '/devis/nouveau', '/audits/request']) ? ' site-header__link--active' : ''}`}
        >
          <ShoppingBag aria-hidden="true" />
          <span>Notre offre</span>
        </summary>
        <div className="site-header__menu-panel">
          <div className="site-header__menu-group">
            <p className="site-header__menu-group-title">Catalogue</p>
            {catalogLinks.map(({ path, label, Icon }) => (
              <Link
                key={path}
                to={path}
                prefetch="intent"
                className={isPathActive(pathname, path) ? 'is-active' : undefined}
                onClick={() => setOfferOpen(false)}
              >
                <Icon aria-hidden="true" />
                <span>{label}</span>
              </Link>
            ))}
          </div>
          <div className="site-header__menu-group">
            <p className="site-header__menu-group-title">Prestations</p>
            {prestationLinks.map(({ path, label, Icon }) => (
              <Link
                key={path}
                to={path}
                prefetch="intent"
                className={isPathActive(pathname, path) ? 'is-active' : undefined}
                onClick={() => setOfferOpen(false)}
              >
                <Icon aria-hidden="true" />
                <span>{label}</span>
              </Link>
            ))}
          </div>
        </div>
      </details>

      <Link
        to="/contact"
        prefetch="intent"
        className={`site-header__link site-header__link--contact${isPathActive(pathname, '/contact') ? ' site-header__link--contact-active' : ''}`}
      >
        <BriefcaseBusiness aria-hidden="true" />
        Contact
      </Link>

      <a
        href={sideStoreAddSourcePath}
        className="site-header__cta"
        rel="noopener noreferrer"
        target="_blank"
      >
        <Download aria-hidden="true" />
        Ajouter a SideStore
      </a>

      <a
        href={iosDownloadPath}
        download="hociatec-altstore-latest.ipa"
        className="site-header__cta"
        rel="noopener noreferrer"
        aria-disabled={publishedIosRelease ? undefined : true}
        onClick={(event) => {
          if (!publishedIosRelease) {
            event.preventDefault();
          }
        }}
      >
        <Download aria-hidden="true" />
        {iosDownloadLabel}
      </a>
    </nav>
  );
};
