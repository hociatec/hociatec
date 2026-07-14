import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { useAuth } from '../../features/auth/hooks/useAuth';
import { useCart } from '@/features/cart/hooks/useCart';
import { IPHONE_DISTRIBUTION_PATH } from '@/features/mobile/config/iphoneDistribution';
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

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  const isAuthenticated = status === 'authenticated' && Boolean(user);

  const handleCatalogSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const trimmed = search.trim();
    navigate(trimmed ? `/catalogue/recherche?q=${encodeURIComponent(trimmed)}` : '/catalogue/recherche');
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
    location.pathname.startsWith('/profile') ||
    location.pathname.startsWith('/appointments/me') ||
    location.pathname.startsWith('/favorites');
  const adminActive = location.pathname.startsWith('/admin');

  return (
    <header className={`site-header site-header--${variant}`}>
      <div className="site-header__container">
        <Link to="/" className="site-header__brand">
          <img src="/logo.png" alt="Hociatec" className="site-header__brand-logo" width={180} height={180} />
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
          <Link
            to={IPHONE_DISTRIBUTION_PATH}
            className={[
              'site-header__link',
              location.pathname === IPHONE_DISTRIBUTION_PATH ? 'site-header__link--active' : '',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            App iPhone
          </Link>
          <button
            type="button"
            className={`site-header__cta${
              location.pathname === '/appointments/book' ? ' site-header__cta--active' : ''
            }`}
            onClick={() => navigate('/appointments/book')}
          >
            Prendre rendez-vous
          </button>
          <button
            type="button"
            className={`site-header__cta${
              location.pathname === '/devis/nouveau' ? ' site-header__cta--active' : ''
            }`}
            onClick={() => navigate('/devis/nouveau')}
          >
            Créer un devis
          </button>
          <button
            type="button"
            className={`site-header__cta${
              location.pathname === '/audits/request' ? ' site-header__cta--active' : ''
            }`}
            onClick={() => navigate('/audits/request')}
          >
            Demander un audit
          </button>
        </nav>
        <div className="site-header__actions" style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'flex-end', gap: '0.75rem', flexWrap: 'wrap', width: '100%' }}>
          {showCatalogSearch && (
            <form onSubmit={handleCatalogSearch} className="site-header__search" role="search" aria-label="Recherche catalogue">
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Rechercher un produit, une marque..."
                aria-label="Rechercher dans le catalogue"
              />
              <button type="submit" className="site-header__search-button">
                Rechercher
              </button>
            </form>
          )}
          <button
            type="button"
            className={linkClass('/panier')}
            onClick={() => navigate('/panier')}
            aria-label={`Mon panier (${cart?.totalQuantity ?? 0})`}
          >
            {`Mon panier (${cart?.totalQuantity ?? 0})`}
          </button>
          {isAuthenticated ? (
            <>
              <Link
                to="/admin"
                className={`site-header__admin-button${
                  adminActive ? ' site-header__admin-button--active' : ''
                }`}
              >
                Admin
              </Link>
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
