import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { FlaskConical, LogIn, Search, ShieldCheck, ShoppingCart, UserPlus } from 'lucide-react';

import { AccountNotifications } from '../../notifications/AccountNotifications';
import { UserAccountMenu } from '../../ui/user-account-menu';
import { isAnyPathActive, isPathActive } from '@/shared/lib/routes';
import { useSiteHeaderActionsState } from './SiteHeaderActionsContext';

interface SiteHeaderActionsProps {
  showCatalogSearch: boolean;
}

export const SiteHeaderActions = ({ showCatalogSearch }: SiteHeaderActionsProps) => {
  const { betaLinkTarget, cartQuantity, isAdmin, isAuthenticated, onLogout, shouldShowBetaLink } =
    useSiteHeaderActionsState();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const [search, setSearch] = useState('');
  const searchId = useId();
  const betaLinkLabel = 'Programme bêta';

  const profileActive = isAnyPathActive(pathname, [
    '/mon-espace',
    '/profile',
    '/appointments/me',
    '/favorites',
    '/quotes/me',
    '/audits/me',
    '/orders/me',
    '/vouchers/me',
  ]);

  const linkClass = (path: string, extraClass = '') =>
    [
      'site-header__link',
      extraClass,
      isPathActive(pathname, path) ? 'site-header__link--active' : '',
    ]
      .filter(Boolean)
      .join(' ');

  const handleGlobalSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const query = search.trim();
    navigate(query ? `/recherche?q=${encodeURIComponent(query)}` : '/recherche');
  };

  const canSubmitSearch = search.trim().length > 0;

  const handleLogout = () => {
    void onLogout();
    navigate('/');
  };

  return (
    <div className="site-header__actions">
      {showCatalogSearch && (
        <form
          onSubmit={handleGlobalSearch}
          className="site-header__search"
          role="search"
          aria-label="Recherche globale"
        >
          <label htmlFor={searchId} className="sr-only">
            Rechercher un produit, un service ou une formation
          </label>
          <Search aria-hidden="true" className="site-header__search-icon" />
          <input
            id={searchId}
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Produits, services, formations..."
          />
          <button
            type="submit"
            className="site-header__search-button"
            aria-label="Rechercher"
            disabled={!canSubmitSearch}
          >
            <Search aria-hidden="true" />
            <span>Rechercher</span>
          </button>
        </form>
      )}
      <Link
        to="/panier"
        className={linkClass('/panier', 'site-header__cart-button')}
        aria-label={`Mon panier (${cartQuantity})`}
      >
        <ShoppingCart aria-hidden="true" />
        <span>Panier</span>
        <span className="site-header__badge">{cartQuantity}</span>
      </Link>
      {shouldShowBetaLink ? (
        <Link
          to={betaLinkTarget}
          className={`site-header__beta-cta${isPathActive(pathname, betaLinkTarget) ? ' site-header__beta-cta--active' : ''}`}
        >
          <FlaskConical aria-hidden="true" />
          <span>{betaLinkLabel}</span>
        </Link>
      ) : null}
      {isAuthenticated ? (
        <>
          {isAdmin && (
            <Link
              to="/admin"
              className={`site-header__admin-button${pathname.startsWith('/admin') ? ' site-header__admin-button--active' : ''}`}
            >
              <ShieldCheck aria-hidden="true" />
              Admin
            </Link>
          )}
          <AccountNotifications />
          <UserAccountMenu onLogout={handleLogout} profileActive={profileActive} />
        </>
      ) : (
        <>
          <Link to="/login" className={linkClass('/login')}>
            <LogIn aria-hidden="true" />
            Se connecter
          </Link>
          <Link
            to="/register"
            className={`site-header__cta${pathname === '/register' ? ' site-header__cta--active' : ''}`}
          >
            <UserPlus aria-hidden="true" />
            S&apos;inscrire
          </Link>
        </>
      )}
    </div>
  );
};
