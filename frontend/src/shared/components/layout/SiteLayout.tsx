import type { PropsWithChildren } from 'react';

import { SiteHeader } from './SiteHeader';
import { SiteFooter } from './SiteFooter';

interface SiteLayoutProps extends PropsWithChildren {
  headerVariant?: 'light' | 'transparent';
}

export const SiteLayout = ({ children, headerVariant = 'transparent' }: SiteLayoutProps) => (
  <div className="site-layout">
    <a href="#main-content" className="skip-link">
      Aller au contenu principal
    </a>
    <SiteHeader variant={headerVariant} />
    <div id="main-content" className="site-layout__content" tabIndex={-1}>
      {children}
    </div>
    <SiteFooter />
  </div>
);
