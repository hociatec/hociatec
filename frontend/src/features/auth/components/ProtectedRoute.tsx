import type { PropsWithChildren } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

import { useAuth } from '../hooks/useAuth';

export const ProtectedRoute = ({ children }: PropsWithChildren) => {
  const { token, status } = useAuth();
  const location = useLocation();

  if (status === 'loading' || status === 'idle') {
    return <p className="notice">Chargement en cours...</p>;
  }

  if (!token) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  return <>{children}</>;
};
