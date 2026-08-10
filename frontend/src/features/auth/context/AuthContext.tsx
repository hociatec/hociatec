import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type PropsWithChildren,
} from 'react';
import { useQueryClient } from '@tanstack/react-query';

import { clearAuthToken, purgeLegacyAuthLocalStorage } from '../../../shared/lib/httpClient';
import axios from 'axios';
import type { AuthUser } from '../../../shared/types/auth';
import {
  fetchCurrentUser,
  loginUser,
  logoutUser,
  type LoginFormPayload,
  updateProfile as updateProfileRequest,
  deleteAccount as deleteAccountRequest,
  type UpdateProfilePayload,
} from '../api/authApi';
import { fetchCart } from '@/features/cart/publicApi';
import { publishAuthSessionEvent, subscribeAuthSessionEvents } from '@/shared/lib/authSessionEvents';
import { logger } from '@/shared/lib/logger';

type AuthStatus = 'idle' | 'loading' | 'authenticated' | 'unauthenticated' | 'unavailable';

interface AuthContextValue {
  user: AuthUser | null;
  status: AuthStatus;
  login: (payload: LoginFormPayload) => Promise<string | undefined>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  updateProfile: (payload: UpdateProfilePayload) => Promise<AuthUser>;
  deleteAccount: () => Promise<void>;
}

const defaultValue: AuthContextValue = {
  user: null,
  status: 'idle',
  login: async () => {
    throw new Error('AuthProvider not mounted');
  },
  logout: async () => {
    throw new Error('AuthProvider not mounted');
  },
  refresh: async () => {
    throw new Error('AuthProvider not mounted');
  },
  updateProfile: async () => {
    throw new Error('AuthProvider not mounted');
  },
  deleteAccount: async () => {
    throw new Error('AuthProvider not mounted');
  },
};

export const AuthContext = createContext<AuthContextValue>(defaultValue);

export const AuthProvider = ({ children }: PropsWithChildren) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [status, setStatus] = useState<AuthStatus>('idle');
  const isMountedRef = useRef(true);
  const skipLocalLoginEventsUntil = useRef(0);
  const queryClient = useQueryClient();

  useEffect(
    () => {
      isMountedRef.current = true;

      return () => {
        isMountedRef.current = false;
      };
    },
    [],
  );

  const setUserIfMounted = useCallback((nextUser: AuthUser | null) => {
    if (isMountedRef.current) setUser(nextUser);
  }, []);

  const setStatusIfMounted = useCallback((nextStatus: AuthStatus) => {
    if (isMountedRef.current) setStatus(nextStatus);
  }, []);

  const clearLocalSessionState = useCallback(
    (nextStatus: AuthStatus = 'unauthenticated') => {
      clearAuthToken();
      queryClient.clear();
      setUserIfMounted(null);
      setStatusIfMounted(nextStatus);
    },
    [queryClient, setStatusIfMounted, setUserIfMounted],
  );

  const loadUser = useCallback(async () => {
    purgeLegacyAuthLocalStorage();

    try {
      setStatusIfMounted('loading');
      const currentUser = await fetchCurrentUser();
      if (!currentUser) {
        clearLocalSessionState();
        return;
      }

      setUserIfMounted(currentUser);
      setStatusIfMounted('authenticated');
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        (error.response?.status === 401 || error.response?.status === 403)
      ) {
        clearLocalSessionState();
        return;
      }

      logger.warn('Unable to fetch current user.', { error });
      setStatusIfMounted('unavailable');
    }
  }, [clearLocalSessionState, setStatusIfMounted, setUserIfMounted]);

  useEffect(() => {
    void loadUser();
  }, [loadUser]);

  useEffect(
    () =>
      subscribeAuthSessionEvents((event) => {
        if (event === 'login' && Date.now() < skipLocalLoginEventsUntil.current) {
          return;
        }

        if (event === 'logout' || event === 'account_deleted') {
          clearLocalSessionState();
          return;
        }

        void loadUser();
      }),
    [clearLocalSessionState, loadUser],
  );

  const login = useCallback(
    async (payload: LoginFormPayload) => {
      setStatusIfMounted('loading');

      try {
        const { rememberMe: _rememberMe = false, ...credentials } = payload;
        const response = await loginUser(credentials);

        await loadUser();
        skipLocalLoginEventsUntil.current = Date.now() + 1000;
        publishAuthSessionEvent('login');

        // Best-effort fetch to trigger potential cart merge server-side and persist token
        try {
          await fetchCart();
        } catch (error) {
          logger.warn('Unable to refresh cart after login.', { error });
          // ignore cart errors during login
        }

        return response.message ?? undefined;
      } catch (error) {
        clearLocalSessionState();

        throw error;
      }
    },
    [clearLocalSessionState, loadUser, setStatusIfMounted],
  );

  const logout = useCallback(async () => {
    clearLocalSessionState();
    publishAuthSessionEvent('logout');

    try {
      await logoutUser();
    } catch (error) {
      logger.warn('Unable to invalidate server session during logout.', { error });
    }
  }, [clearLocalSessionState]);

  const updateProfile = useCallback(async (payload: UpdateProfilePayload) => {
    const updatedUser = await updateProfileRequest(payload);
    setUserIfMounted(updatedUser);
    setStatusIfMounted('authenticated');
    publishAuthSessionEvent('profile_updated');

    return updatedUser;
  }, [setStatusIfMounted, setUserIfMounted]);

  const deleteAccount = useCallback(async () => {
    await deleteAccountRequest();
    publishAuthSessionEvent('account_deleted');
    await logout();
  }, [logout]);

  const value = useMemo(
    () => ({
      user,
      status,
      login,
      logout,
      refresh: loadUser,
      updateProfile,
      deleteAccount,
    }),
    [deleteAccount, loadUser, login, logout, status, updateProfile, user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
