import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { LoadingState } from '@/shared/components/ui/page-state';

export const AdminRoute = ({ children }: PropsWithChildren) => {
  const { isAdmin, loading } = useRequireAdmin();
  const location = useLocation();

  if (loading) {
    return <LoadingState className="min-h-[40vh]">Vérification des droits admin...</LoadingState>;
  }

  if (!isAdmin) {
    return <Navigate to="/" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
