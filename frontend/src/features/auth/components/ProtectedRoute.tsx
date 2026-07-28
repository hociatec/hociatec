import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router';

import { LoadingState } from '@/shared/components/ui/page-state';
import { useAuth } from '../hooks/useAuth';

export const ProtectedRoute = ({ children }: PropsWithChildren) => {
  const { status } = useAuth();
  const location = useLocation();

  if (status === 'loading' || status === 'idle') {
    return <LoadingState className="min-h-[40vh]">Vérification de la session...</LoadingState>;
  }

  if (status !== 'authenticated') {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
