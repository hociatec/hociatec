import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { FlaskConical, LogIn, Mail, Search, ShieldCheck, ShoppingCart, UserPlus } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { fetchMyBetaProfile } from '@/features/betaTest/api/betaApi';

import { useAuth } from '@/features/auth/hooks/useAuth';
import { useCart } from '@/features/cart/hooks/useCart';
import { AccountNotifications } from '../AccountNotifications';
import { UserAccountMenu } from '../ui/user-account-menu';
import { isAnyPathActive, isPathActive } from '@/shared/lib/routes';

interface SiteHeaderActionsProps {
  showCatalogSearch: boolean;
}

export const SiteHeaderActions = ({ showCatalogSearch }: SiteHeaderActionsProps) => {
  const { user, status, logout } = useAuth();
  const { cart } = useCart();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const [search, setSearch] = useState('');
  const searchId = useId();

  const isAuthenticated = status === 'authenticated' && Boolean(user);
  const isAdmin = (user?.roles ?? []).includes('ROLE_ADMIN');

  const { data: betaProfile } = useQuery({
    queryKey: ['betaProfile'],
    queryFn: fetchMyBetaProfile,
    enabled: isAuthenticated,
    retry: false,
  });
  const isBetaTester = Boolean(betaProfile);
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
    logout();
    navigate('/');
  };

  return (
    <div className="site-header__actions">
      {!isBetaTester && (
        <Link to="/beta-test" className={linkClass('/beta-test')}>
          <FlaskConical aria-hidden="true" />
          Bêta-test
        </Link>
      )}
      <Link to="/contact" className={linkClass('/contact')}>
        <Mail aria-hidden="true" />
        Contact
      </Link>
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
      <button
        type="button"
        className={linkClass('/panier', 'site-header__cart-button')}
        onClick={() => navigate('/panier')}
        aria-label={`Mon panier (${cart?.totalQuantity ?? 0})`}
      >
        <ShoppingCart aria-hidden="true" />
        <span>Panier</span>
        <span className="site-header__badge">{cart?.totalQuantity ?? 0}</span>
      </button>
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
