import { Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router';
import { LoadingState } from '@/shared/components/ui/page-state';
import { RouteErrorBoundary } from '@/shared/components/system/ErrorBoundary';
import { adminRoutes, protectedRoutes, publicRoutes, renderRoutes } from './AppRouteConfig';

const RouteFallback = () => (
  <div className="site-layout">
    <div className="site-layout__content">
      <LoadingState className="min-h-[40vh]">Chargement de la page...</LoadingState>
    </div>
  </div>
);

export const AppRoutes = () => (
  <RouteErrorBoundary>
    <Suspense fallback={<RouteFallback />}>
      <Routes>
        {renderRoutes(publicRoutes, 'public')}
        {renderRoutes(protectedRoutes, 'protected')}
        {renderRoutes([adminRoutes], 'admin')}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Suspense>
  </RouteErrorBoundary>
);
