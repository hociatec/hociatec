import { Route, Routes } from 'react-router';
import { RouteErrorBoundary } from '@/shared/components/system/ErrorBoundary';
import { adminRoutes, protectedRoutes, publicRoutes, renderRoutes } from './AppRouteConfig';
import { NotFoundPage } from '@/features/notFound/publicApi';

export const AppRoutes = () => (
  <RouteErrorBoundary>
    <Routes>
      {renderRoutes(publicRoutes, 'public')}
      {renderRoutes(protectedRoutes, 'protected')}
      {renderRoutes([adminRoutes], 'admin')}
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  </RouteErrorBoundary>
);
