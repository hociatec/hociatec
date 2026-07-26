import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
  type PropsWithChildren,
} from 'react';

import { clearAuthToken, purgeLegacyAuthLocalStorage } from '../../../shared/lib/httpClient';
import axios from 'axios';
import type { AuthUser } from '../../../shared/types/auth';
import {
  fetchCurrentUser,
  loginUser,
  logoutUser,
  refreshUserSession,
  type LoginFormPayload,
  updateProfile as updateProfileRequest,
  deleteAccount as deleteAccountRequest,
  type UpdateProfilePayload,
} from '../api/authApi';
import { fetchCart } from '@/features/cart/api/cartApi';

interface AuthContextValue {
  user: AuthUser | null;
  status: 'idle' | 'loading' | 'authenticated' | 'unauthenticated';
  login: (payload: LoginFormPayload) => Promise<void>;
  logout: () => void;
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
  logout: () => {
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
  const [status, setStatus] = useState<'idle' | 'loading' | 'authenticated' | 'unauthenticated'>(
    'idle',
  );

  const loadUser = useCallback(async () => {
    purgeLegacyAuthLocalStorage();

    try {
      setStatus('loading');
      const currentUser = await fetchCurrentUser();
      setUser(currentUser);
      setStatus('authenticated');
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        (error.response?.status === 401 || error.response?.status === 403)
      ) {
        try {
          await refreshUserSession();

          const currentUser = await fetchCurrentUser();
          setUser(currentUser);
          setStatus('authenticated');
          return;
        } catch (refreshError) {
          console.debug('No active authentication session to refresh.', refreshError);
        }

        clearAuthToken();
        setUser(null);
        setStatus('unauthenticated');
        return;
      }

      console.error('Unable to fetch current user', error);
      setUser(null);
      setStatus('unauthenticated');
    }
  }, []);

  useEffect(() => {
    void loadUser();
  }, [loadUser]);

  const login = useCallback(
    async (payload: LoginFormPayload) => {
      setStatus('loading');

      try {
        const { rememberMe: _rememberMe = false, ...credentials } = payload;
        await loginUser(credentials);

        await loadUser();

        // Best-effort fetch to trigger potential cart merge server-side and persist token
        try {
          await fetchCart();
        } catch {
          // ignore cart errors during login
        }
      } catch (error) {
        clearAuthToken();
        setUser(null);
        setStatus('unauthenticated');

        throw error;
      }
    },
    [loadUser],
  );

  const logout = useCallback(() => {
    void logoutUser().catch(() => {
      // Local cleanup still happens even if the server is unavailable.
    });
    clearAuthToken();
    setUser(null);
    setStatus('unauthenticated');
  }, []);

  const updateProfile = useCallback(async (payload: UpdateProfilePayload) => {
    const updatedUser = await updateProfileRequest(payload);
    setUser(updatedUser);
    setStatus('authenticated');

    return updatedUser;
  }, []);

  const deleteAccount = useCallback(async () => {
    await deleteAccountRequest();
    logout();
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
