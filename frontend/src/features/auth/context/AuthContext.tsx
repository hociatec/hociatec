import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
  type PropsWithChildren,
} from 'react';

import {
  clearAuthToken,
  getPersistedToken,
  persistAuthToken,
} from '../../../shared/lib/httpClient';
import type { AuthUser } from '../../../shared/types/auth';
import {
  fetchCurrentUser,
  loginUser,
  type LoginPayload,
  updateProfile as updateProfileRequest,
  deleteAccount as deleteAccountRequest,
  type UpdateProfilePayload,
} from '../api/authApi';
import { fetchCart } from '@/features/cart/api';

interface AuthContextValue {
  user: AuthUser | null;
  token: string | null;
  status: 'idle' | 'loading' | 'authenticated' | 'unauthenticated';
  login: (payload: LoginPayload) => Promise<void>;
  logout: () => void;
  refresh: () => Promise<void>;
  updateProfile: (payload: UpdateProfilePayload) => Promise<AuthUser>;
  deleteAccount: () => Promise<void>;
}

const defaultValue: AuthContextValue = {
  user: null,
  token: null,
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
  const [token, setToken] = useState<string | null>(getPersistedToken());
  const [status, setStatus] = useState<
    'idle' | 'loading' | 'authenticated' | 'unauthenticated'
  >('idle');

  const loadUser = useCallback(async () => {
    const persistedToken = getPersistedToken();

    if (!persistedToken) {
      setToken(null);
      setUser(null);
      setStatus('unauthenticated');
      return;
    }

    try {
      setStatus('loading');
      const currentUser = await fetchCurrentUser();
      setToken(persistedToken);
      setUser(currentUser);
      setStatus('authenticated');
    } catch (error) {
      console.error('Unable to fetch current user', error);
      clearAuthToken();
      setToken(null);
      setUser(null);
      setStatus('unauthenticated');
    }
  }, []);

  useEffect(() => {
    void loadUser();
  }, [loadUser]);

  const login = useCallback(
    async (payload: LoginPayload) => {
      setStatus('loading');

      const tokens = await loginUser(payload);
      persistAuthToken(tokens.token);
      setToken(tokens.token);

      await loadUser();

      // Best-effort fetch to trigger potential cart merge server-side and persist token
      try {
        await fetchCart();
      } catch (e) {
        // ignore cart errors during login
      }
    },
    [loadUser],
  );

  const logout = useCallback(() => {
    clearAuthToken();
    setToken(null);
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
      token,
      status,
      login,
      logout,
      refresh: loadUser,
      updateProfile,
      deleteAccount,
    }),
    [deleteAccount, loadUser, login, logout, status, token, updateProfile, user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
