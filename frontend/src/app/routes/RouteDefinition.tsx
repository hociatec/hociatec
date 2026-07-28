import type { ReactNode } from 'react';

import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';

export interface AppRouteDefinition {
  path?: string;
  index?: boolean;
  element?: ReactNode;
  children?: AppRouteDefinition[];
}

export const protectedElement = (element: ReactNode) => <ProtectedRoute>{element}</ProtectedRoute>;
