import { Link } from 'react-router';

import { SiteHeaderActions } from './siteHeader/SiteHeaderActions';
import { SiteHeaderNavigation } from './siteHeader/SiteHeaderNavigation';

interface SiteHeaderProps {
  variant?: 'light' | 'transparent';
  showCatalogSearch?: boolean;
}

export const SiteHeader = ({
  variant = 'transparent',
  showCatalogSearch = true,
}: SiteHeaderProps) => (
  <header className={`site-header site-header--${variant}`}>
    <div className="site-header__container">
      <Link to="/" className="site-header__brand">
        <img
          src="/logo.png"
          alt="Hociatec"
          className="site-header__brand-logo"
          width={180}
          height={180}
          loading="eager"
          decoding="async"
          fetchPriority="high"
        />
      </Link>
      <SiteHeaderNavigation />
      <SiteHeaderActions showCatalogSearch={showCatalogSearch} />
    </div>
    <div
      id="site-header-toasts"
      className="site-header__toasts"
      aria-live="assertive"
      aria-atomic="true"
    />
  </header>
);
