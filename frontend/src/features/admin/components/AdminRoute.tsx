import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router';

import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useAuth } from '@/features/auth/publicApi';

export const AdminRoute = ({ children }: PropsWithChildren) => {
  const { refresh } = useAuth();
  const { isAdmin, loading, unavailable } = useRequireAdmin();
  const location = useLocation();

  if (loading) {
    return <LoadingState className="min-h-[40vh]">Vérification des droits admin...</LoadingState>;
  }

  if (unavailable) {
    return (
      <ErrorState className="min-h-[40vh]" actionLabel="Réessayer" onAction={() => void refresh()}>
        Impossible de vérifier vos droits admin pour le moment.
      </ErrorState>
    );
  }

  if (!isAdmin) {
    return <Navigate to="/" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
