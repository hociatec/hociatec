import { Link, useLocation, useNavigate } from 'react-router-dom';

import { useAuth } from '../../features/auth/hooks/useAuth';
import { useCart } from '@/features/cart/hooks/useCart';
import { UserAccountMenu } from './ui/user-account-menu';

interface SiteHeaderProps {
  variant?: 'light' | 'transparent';
}

export const SiteHeader = ({ variant = 'transparent' }: SiteHeaderProps) => {
  const { user, status, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const { cart } = useCart();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  const isAuthenticated = status === 'authenticated' && Boolean(user);

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
          <img src="/logo.png" alt="Logo Hociatec" className="site-header__brand-logo" width={40} height={40} />
          <span>hociatec</span>
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
        <div className="site-header__actions">
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
