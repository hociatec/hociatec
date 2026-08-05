import { useAuth } from '@/features/auth/hooks/useAuth';
import { hasPermission } from '@/features/auth/lib/permissions';

export const useRequireAdmin = () => {
  const { user, status } = useAuth();

  const isAdmin = hasPermission(user, 'admin.access');

  const loading = status === 'loading' || status === 'idle';

  return {
    isAdmin,
    loading,
  };
};
