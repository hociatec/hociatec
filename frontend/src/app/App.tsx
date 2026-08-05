import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { AuthProvider } from '@/features/auth/context/AuthContext';
import { CartProvider } from '@/features/cart/context/CartContext';
import { AppRoutes } from './routes/AppRoutes';
import { ConfirmProvider } from '@/shared/components/ui/confirm';
import { PromptProvider } from '@/shared/components/ui/prompt';
import { ToastProvider } from '@/shared/components/ui/toast';
import { AccessibilityAnnouncer } from '@/shared/components/accessibility/AccessibilityAnnouncer';
import { MaintenanceGate } from '@/shared/components/system/MaintenanceGate';
import { normalizeHttpError } from '@/shared/lib/httpClient';

export const AppProviders = () => (
  <AuthProvider>
    <ToastProvider>
      <ConfirmProvider>
        <PromptProvider>
          <CartProvider>
            <MaintenanceGate>
              <AppRoutes />
            </MaintenanceGate>
            <AccessibilityAnnouncer />
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
      staleTime: 30_000,
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
