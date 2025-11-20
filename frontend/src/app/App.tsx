import { BrowserRouter } from 'react-router-dom';

import { AuthProvider } from '@/features/auth/context/AuthContext';
import { CartProvider } from '@/features/cart/context/CartContext';
import { AppRoutes } from './routes/AppRoutes';
import { ToastProvider } from '@/shared/components/ui/toast';

export const AppProviders = () => (
  <AuthProvider>
    <ToastProvider>
      <CartProvider>
        <AppRoutes />
      </CartProvider>
    </ToastProvider>
  </AuthProvider>
);

export const App = () => (
  <BrowserRouter>
    <AppProviders />
  </BrowserRouter>
);
