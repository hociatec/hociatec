import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';

export const AdminRoute = ({ children }: PropsWithChildren) => {
  const { isAdmin, loading } = useRequireAdmin();
  const location = useLocation();

  if (loading) {
    return <p className="notice">Chargement en cours...</p>;
  }

  if (!isAdmin) {
    return <Navigate to="/" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
