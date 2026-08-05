import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { AuthProvider } from '@/features/auth/publicApi';
import { CartProvider } from '@/features/cart/publicApi';
import { useAuth } from '@/features/auth/publicApi';
import { hasPermission } from '@/features/auth/publicApi';
import { useCart } from '@/features/cart/publicApi';
import { AppRoutes } from './routes/AppRoutes';
import { ConfirmProvider } from '@/shared/components/ui/confirm';
import { PromptProvider } from '@/shared/components/ui/prompt';
import { ToastProvider } from '@/shared/components/ui/toast';
import { AccessibilityAnnouncer } from '@/shared/components/accessibility/AccessibilityAnnouncer';
import { MaintenanceGate } from '@/shared/components/system/MaintenanceGate';
import { NetworkStatusBanner } from '@/shared/components/system/NetworkStatusBanner';
import { normalizeHttpError } from '@/shared/lib/httpClient';
import { SiteHeaderActionsProvider } from '@/shared/components/layout/siteHeader/SiteHeaderActionsContext';

const AppHeaderActionsProvider = ({ children }: { children: React.ReactNode }) => {
  const { user, status, logout } = useAuth();
  const { cart } = useCart();

  return (
    <SiteHeaderActionsProvider
      value={{
        cartQuantity: cart?.totalQuantity ?? 0,
        isAdmin: hasPermission(user, 'admin.access'),
        isAuthenticated: status === 'authenticated' && Boolean(user),
        onLogout: logout,
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
