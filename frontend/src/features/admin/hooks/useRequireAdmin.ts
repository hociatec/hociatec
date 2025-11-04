import { useMemo } from 'react';

import { useAuth } from '@/features/auth/hooks/useAuth';

export const useRequireAdmin = () => {
  const { user, status } = useAuth();

  const isAdmin = useMemo(() => {
    const roles = user?.roles ?? [];
    return roles.includes('ROLE_ADMIN');
  }, [user]);

  const loading = status === 'loading' || status === 'idle';

  return {
    isAdmin,
    loading,
  };
};

