import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';

import { AuthProvider } from '@/features/auth/publicApi';
import { CartProvider } from '@/features/cart/publicApi';
import { useAuth } from '@/features/auth/publicApi';
import { hasPermission } from '@/features/auth/publicApi';
import { useCart } from '@/features/cart/publicApi';
import { fetchMyBetaProfile } from '@/features/betaTest/publicApi';
import { AppRoutes } from './routes/AppRoutes';
import { ConfirmProvider } from '@/shared/components/ui/confirm';
import { PromptProvider } from '@/shared/components/ui/prompt';
import { ToastProvider } from '@/shared/components/ui/toast';
import { AccessibilityAnnouncer } from '@/shared/components/accessibility/AccessibilityAnnouncer';
import { MaintenanceGate } from '@/shared/components/system/MaintenanceGate';
import { NetworkStatusBanner } from '@/shared/components/system/NetworkStatusBanner';
import { normalizeHttpError } from '@/shared/lib/httpClient';
import { SiteHeaderActionsProvider } from '@/shared/components/layout/siteHeader/SiteHeaderActionsContext';
import { isFeatureEnabled } from '@/shared/config/featureFlags';
import { betaQueryKeys } from '@/shared/lib/queryKeys';

const AppHeaderActionsProvider = ({ children }: { children: React.ReactNode }) => {
  const { user, status, logout } = useAuth();
  const { cart } = useCart();
  const isAuthenticated = status === 'authenticated' && Boolean(user);
  const isBetaProgramEnabled = isFeatureEnabled('betaProgram');
  const { data: betaProfile, isFetched: hasFetchedBetaProfile } = useQuery({
    queryKey: betaQueryKeys.profile(),
    queryFn: fetchMyBetaProfile,
    enabled: isBetaProgramEnabled && isAuthenticated,
    retry: false,
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
            </AppHeaderActionsProvider>
          </CartProvider>
        </PromptProvider>
      </ConfirmProvider>
    </ToastProvider>
  </AuthProvider>
);

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: true,
      refetchOnReconnect: true,
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      retry: (failureCount, error) => {
        const normalized = normalizeHttpError(error);
        if (
          normalized.kind === 'authentication' ||
          normalized.kind === 'authorization' ||
          normalized.kind === 'validation' ||
          normalized.kind === 'conflict' ||
          normalized.kind === 'rate_limit'
        ) {
          return false;
        }

        return failureCount < 1;
      },
    },
    mutations: {
      retry: false,
    },
  },
});

export const App = () => (
  <QueryClientProvider client={queryClient}>
    <BrowserRouter>
      <AppProviders />
    </BrowserRouter>
  </QueryClientProvider>
);
