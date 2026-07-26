import { useAuth } from '@/features/auth/hooks/useAuth';

export const useRequireAdmin = () => {
  const { user, status } = useAuth();

  const isAdmin = (user?.roles ?? []).includes('ROLE_ADMIN');

  const loading = status === 'loading' || status === 'idle';

  return {
    isAdmin,
    loading,
  };
};
