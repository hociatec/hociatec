import { useAuth } from '@/features/auth/publicApi';
import { hasPermission } from '@/features/auth/publicApi';

export const useRequireAdmin = () => {
  const { user, status } = useAuth();

  const isAdmin = hasPermission(user, 'admin.access');

  const loading = status === 'loading' || status === 'idle';

  return {
    isAdmin,
    loading,
  };
};
