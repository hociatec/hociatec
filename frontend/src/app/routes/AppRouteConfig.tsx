import { Suspense } from 'react';
import { Route } from 'react-router';

import { LoadingState } from '@/shared/components/ui/page-state';
import { adminRoutes } from './AdminRoutes';
import { protectedRoutes } from './ProtectedRoutes';
import { publicRoutes } from './PublicRoutes';
import type { AppRouteDefinition } from './RouteDefinition';

export { adminRoutes, protectedRoutes, publicRoutes };
export type { AppRouteDefinition };

const RouteFallback = () => (
  <LoadingState className="min-h-[40vh]">Chargement de la page...</LoadingState>
);

const withRouteSuspense = (element: AppRouteDefinition['element']) =>
  element ? <Suspense fallback={<RouteFallback />}>{element}</Suspense> : undefined;

export const renderRoutes = (routes: AppRouteDefinition[], parentKey = 'route') =>
  routes.map((route, index) => {
    const key = `${parentKey}-${route.path ?? 'index'}-${index}`;
    const children = route.children
      ? renderRoutes(route.children, `${parentKey}-${route.path ?? index}`)
      : null;

    if (route.index) return <Route key={key} index element={withRouteSuspense(route.element)} />;

    return (
      <Route key={key} path={route.path} element={withRouteSuspense(route.element)}>
        {children}
      </Route>
    );
  });
