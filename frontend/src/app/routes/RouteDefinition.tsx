import type { ReactNode } from 'react';

import { ProtectedRoute } from '@/features/auth/publicApi';
import { PrivateRouteMeta } from '@/shared/components/seo/PrivateRouteMeta';

export interface AppRouteDefinition {
  path?: string;
  index?: boolean;
  element?: ReactNode;
  children?: AppRouteDefinition[];
}

export const protectedElement = (element: ReactNode) => (
  <>
    <PrivateRouteMeta />
    <ProtectedRoute>{element}</ProtectedRoute>
  </>
);
