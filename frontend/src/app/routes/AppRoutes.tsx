import { Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { LoadingState } from '@/shared/components/ui/page-state';
import { adminRoutes, protectedRoutes, publicRoutes, renderRoutes } from './AppRouteConfig';

const RouteFallback = () => (
  <div className="site-layout">
    <div className="site-layout__content">
      <LoadingState className="min-h-[40vh]">Chargement de la page...</LoadingState>
    </div>
  </div>
);

export const AppRoutes = () => (
  <Suspense fallback={<RouteFallback />}>
    <Routes>
      {renderRoutes(publicRoutes, 'public')}
      {renderRoutes(protectedRoutes, 'protected')}
      {renderRoutes([adminRoutes], 'admin')}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  </Suspense>
);
