import type { AuthUser } from '@/shared/types/auth';

export const hasPermission = (user: AuthUser | null | undefined, permission: string) =>
  (user?.permissions ?? []).includes(permission);
