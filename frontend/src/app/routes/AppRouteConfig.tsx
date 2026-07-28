import { Route } from 'react-router';

import { adminRoutes } from './AdminRoutes';
import { protectedRoutes } from './ProtectedRoutes';
import { publicRoutes } from './PublicRoutes';
import type { AppRouteDefinition } from './RouteDefinition';

export { adminRoutes, protectedRoutes, publicRoutes };
export type { AppRouteDefinition };

export const renderRoutes = (routes: AppRouteDefinition[], parentKey = 'route') =>
  routes.map((route, index) => {
    const key = `${parentKey}-${route.path ?? 'index'}-${index}`;
    const children = route.children
      ? renderRoutes(route.children, `${parentKey}-${route.path ?? index}`)
      : null;

    if (route.index) return <Route key={key} index element={route.element} />;

    return (
      <Route key={key} path={route.path} element={route.element}>
        {children}
      </Route>
    );
  });
