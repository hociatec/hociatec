import type { PropsWithChildren } from 'react';

import { SiteHeader } from './SiteHeader';
import { SiteFooter } from './SiteFooter';

interface SiteLayoutProps extends PropsWithChildren {
  headerVariant?: 'light' | 'transparent';
}

export const SiteLayout = ({ children, headerVariant = 'transparent' }: SiteLayoutProps) => (
  <div className="site-layout">
    <SiteHeader variant={headerVariant} />
    <div className="site-layout__content">{children}</div>
    <SiteFooter />
  </div>
);
