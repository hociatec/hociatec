import { BrowserRouter } from 'react-router-dom';

import { AuthProvider } from '@/features/auth/context/AuthContext';
import { CartProvider } from '@/features/cart/context/CartContext';
import { AppRoutes } from './routes/AppRoutes';
import { ConfirmProvider } from '@/shared/components/ui/confirm';
import { PromptProvider } from '@/shared/components/ui/prompt';
import { ToastProvider } from '@/shared/components/ui/toast';
import { AccessibilityAnnouncer } from '@/shared/components/AccessibilityAnnouncer';
import { MaintenanceGate } from '@/shared/components/MaintenanceGate';

export const AppProviders = () => (
  <AuthProvider>
    <ToastProvider>
      <ConfirmProvider>
        <PromptProvider>
          <CartProvider>
            <AccessibilityAnnouncer />
            <MaintenanceGate>
              <AppRoutes />
            </MaintenanceGate>
          </CartProvider>
        </PromptProvider>
      </ConfirmProvider>
    </ToastProvider>
  </AuthProvider>
);

export const App = () => (
  <BrowserRouter>
    <AppProviders />
  </BrowserRouter>
);
