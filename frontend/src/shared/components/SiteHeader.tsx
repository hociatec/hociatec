import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import { CalendarDays, ClipboardCheck, FileText, Search, ShoppingCart } from 'lucide-react';

import { useAuth } from '../../features/auth/hooks/useAuth';
import { useCart } from '@/features/cart/hooks/useCart';
import { UserAccountMenu } from './ui/user-account-menu';

interface SiteHeaderProps {
  variant?: 'light' | 'transparent';
  showCatalogSearch?: boolean;
}

export const SiteHeader = ({ variant = 'transparent', showCatalogSearch = true }: SiteHeaderProps) => {
  const { user, status, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const { cart } = useCart();
  const [search, setSearch] = useState('');
  const [servicesOpen, setServicesOpen] = useState(false);
  const servicesSummaryRef = useRef<HTMLElement | null>(null);

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  const isAuthenticated = status === 'authenticated' && Boolean(user);
  const isAdmin = (user?.roles ?? []).includes('ROLE_ADMIN');

  const handleCatalogSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const trimmed = search.trim();
    navigate(trimmed ? `/catalogue/recherche?q=${encodeURIComponent(trimmed)}` : '/catalogue/recherche');
  };

  const closeServicesMenu = () => {
    setServicesOpen(false);
    servicesSummaryRef.current?.focus();
  };

  const handleServicesKeyDown = (event: KeyboardEvent<HTMLDetailsElement>) => {
    if (event.key !== 'Escape') {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    closeServicesMenu();
  };

  const navigateFromServices = (path: string) => {
    setServicesOpen(false);
    navigate(path);
  };

  const linkClass = (path: string, extraClass = '') =>
    [
      'site-header__link',
      extraClass,
      location.pathname === path || location.pathname.startsWith(`${path}/`)
        ? 'site-header__link--active'
        : '',
    ]
      .filter(Boolean)
      .join(' ')
      .trim();

  const profileActive =
    location.pathname.startsWith('/mon-espace') ||
    location.pathname.startsWith('/profile') ||
    location.pathname.startsWith('/appointments/me') ||
    location.pathname.startsWith('/favorites') ||
    location.pathname.startsWith('/quotes/me') ||
    location.pathname.startsWith('/audits/me') ||
    location.pathname.startsWith('/orders/me') ||
    location.pathname.startsWith('/vouchers/me');
  const adminActive = location.pathname.startsWith('/admin');

  return (
    <header className={`site-header site-header--${variant}`}>
      <div className="site-header__container">
        <Link to="/" className="site-header__brand">
          <img src="/logo.png" alt="Hociatec" className="site-header__brand-logo" width={180} height={180} />
          <span className="site-header__brand-name">Hociatec</span>
        </Link>
        <nav className="site-header__nav" aria-label="Navigation principale">
          <Link
            to="/catalogue/vente"
            className={[
              'site-header__link',
              location.pathname.startsWith('/catalogue/vente') ? 'site-header__link--active' : '',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            Vente
          </Link>
          <Link
            to="/catalogue/location"
            className={[
              'site-header__link',
              location.pathname.startsWith('/catalogue/location') ? 'site-header__link--active' : '',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            Location
          </Link>
          <Link
            to="/services"
            className={[
              'site-header__link',
              location.pathname === '/services' ? 'site-header__link--active' : '',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            Services
          </Link>
          <details
            className="site-header__service-menu"
            open={servicesOpen}
            onToggle={(event) => setServicesOpen(event.currentTarget.open)}
            onKeyDown={handleServicesKeyDown}
          >
            <summary ref={servicesSummaryRef} className="site-header__service-trigger">Nos prestations</summary>
            <div className="site-header__service-panel">
              <button type="button" onClick={() => navigateFromServices('/appointments/book')}>
                <CalendarDays aria-hidden="true" />
                <span>Prendre rendez-vous</span>
              </button>
              <button type="button" onClick={() => navigateFromServices('/devis/nouveau')}>
                <FileText aria-hidden="true" />
                <span>Créer un devis</span>
              </button>
              <button type="button" onClick={() => navigateFromServices('/audits/request')}>
                <ClipboardCheck aria-hidden="true" />
                <span>Demander un audit</span>
              </button>
            </div>
          </details>
        </nav>
        <div className="site-header__actions">
          {showCatalogSearch && (
            <form onSubmit={handleCatalogSearch} className="site-header__search" role="search" aria-label="Recherche catalogue">
              <Search aria-hidden="true" className="site-header__search-icon" />
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Rechercher un produit, une marque..."
                aria-label="Rechercher dans le catalogue"
              />
              <button type="submit" className="site-header__search-button">
                <Search aria-hidden="true" />
                <span>Rechercher</span>
              </button>
            </form>
          )}
          <button
            type="button"
            className={linkClass('/panier')}
            onClick={() => navigate('/panier')}
            aria-label={`Mon panier (${cart?.totalQuantity ?? 0})`}
          >
            <ShoppingCart aria-hidden="true" />
            <span>Panier</span>
            <span className="site-header__badge">{cart?.totalQuantity ?? 0}</span>
          </button>
          {isAuthenticated ? (
            <>
              {isAdmin ? (
                <Link
                  to="/admin"
                  className={`site-header__admin-button${
                    adminActive ? ' site-header__admin-button--active' : ''
                  }`}
                >
                  Admin
                </Link>
              ) : null}
              <UserAccountMenu onLogout={handleLogout} profileActive={profileActive} />
            </>
          ) : (
            <>
              <Link to="/login" className={linkClass('/login')}>
                Se connecter
              </Link>
              <Link
                to="/register"
                className={`site-header__cta${location.pathname === '/register' ? ' site-header__cta--active' : ''}`}
              >
                S&apos;inscrire
              </Link>
            </>
          )}
        </div>
      </div>
      <div id="site-header-toasts" className="site-header__toasts" aria-live="assertive" aria-atomic="true" />
    </header>
  );
};
