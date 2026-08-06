import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router';

import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useAuth } from '../hooks/useAuth';

export const ProtectedRoute = ({ children }: PropsWithChildren) => {
  const { refresh, status } = useAuth();
  const location = useLocation();

  if (status === 'loading' || status === 'idle') {
    return <LoadingState className="min-h-[40vh]">Vérification de la session...</LoadingState>;
  }

  if (status === 'unavailable') {
    return (
      <ErrorState className="min-h-[40vh]" actionLabel="Réessayer" onAction={() => void refresh()}>
        Impossible de vérifier votre session pour le moment.
      </ErrorState>
    );
  }

  if (status !== 'authenticated') {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
