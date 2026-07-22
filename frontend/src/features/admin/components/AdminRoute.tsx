import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';

export const AdminRoute = ({ children }: PropsWithChildren) => {
  const { isAdmin, loading } = useRequireAdmin();
  const location = useLocation();

  if (loading) {
    return <div aria-hidden="true" className="min-h-[40vh]" />;
  }

  if (!isAdmin) {
    return <Navigate to="/" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
