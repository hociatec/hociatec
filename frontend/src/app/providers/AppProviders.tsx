import { useQuery } from '@tanstack/react-query';
import type { ReactNode } from 'react';

import { AppRoutes } from '@/app/routes/AppRoutes';
import { fetchMyBetaProfile } from '@/features/betaTest/publicApi';
import { AuthProvider, hasPermission, useAuth } from '@/features/auth/publicApi';
import { CartProvider, useCart } from '@/features/cart/publicApi';
import { AccessibilityAnnouncer } from '@/shared/components/accessibility/AccessibilityAnnouncer';
import { PageFocusHandler } from '@/shared/components/accessibility/PageFocusHandler';
import { SiteHeaderActionsProvider } from '@/shared/components/layout/siteHeader/SiteHeaderActionsContext';
import { MaintenanceGate } from '@/shared/components/system/MaintenanceGate';
import { NetworkStatusBanner } from '@/shared/components/system/NetworkStatusBanner';
import { ConfirmProvider } from '@/shared/components/ui/confirm';
import { PromptProvider } from '@/shared/components/ui/prompt';
import { ToastProvider } from '@/shared/components/ui/toast';
import { isFeatureEnabled } from '@/shared/config/featureFlags';
import { betaQueryKeys } from '@/features/betaTest/queryKeys';

const AppHeaderActionsProvider = ({ children }: { children: ReactNode }) => {
  const { user, status, logout } = useAuth();
  const { cart } = useCart();
  const isAuthenticated = status === 'authenticated' && Boolean(user);
  const isBetaProgramEnabled = isFeatureEnabled('betaProgram');
  const { data: betaProfile, isFetched: hasFetchedBetaProfile } = useQuery({
    queryKey: betaQueryKeys.profile(),
    queryFn: fetchMyBetaProfile,
    enabled: isBetaProgramEnabled && isAuthenticated,
    retry: false,
    staleTime: 5 * 60_000,
  });

  return (
    <SiteHeaderActionsProvider
      value={{
        betaLinkTarget: isAuthenticated ? '/beta' : '/beta-test',
        cartQuantity: cart?.totalQuantity ?? 0,
        isAdmin: hasPermission(user, 'admin.access'),
        isAuthenticated,
        onLogout: logout,
        shouldShowBetaLink:
          isBetaProgramEnabled && (!isAuthenticated || (hasFetchedBetaProfile && betaProfile === null)),
      }}
    >
      {children}
    </SiteHeaderActionsProvider>
  );
};

export const AppProviders = () => (
  <AuthProvider>
    <ToastProvider>
      <ConfirmProvider>
        <PromptProvider>
          <CartProvider>
            <AppHeaderActionsProvider>
              <NetworkStatusBanner />
              <MaintenanceGate>
                <AppRoutes />
              </MaintenanceGate>
              <AccessibilityAnnouncer />
              <PageFocusHandler />
            </AppHeaderActionsProvider>
          </CartProvider>
        </PromptProvider>
      </ConfirmProvider>
    </ToastProvider>
  </AuthProvider>
);
