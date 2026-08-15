// @vitest-environment jsdom
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import { MemoryRouter, Route, Routes } from 'react-router';

import { AdminRoute } from '@/features/admin/publicApi';
import { AuthContext } from '@/features/auth/context/AuthContext';
import { ProtectedRoute } from './ProtectedRoute';
import type { AuthUser } from '@/shared/types/auth';

const baseUser: AuthUser = {
  id: 1,
  email: 'client@hociatec.fr',
  firstName: 'Client',
  lastName: 'Hociatec',
  address: '',
  postalCode: '',
  city: '',
  birthDate: '',
  phoneNumber: '',
  gender: '',
  roles: ['ROLE_USER'],
  permissions: [],
};

afterEach(() => {
  cleanup();
});

const authValue = (status: React.ContextType<typeof AuthContext>['status'], user: AuthUser | null) => ({
  user,
  status,
  login: async () => undefined,
  logout: async () => undefined,
  refresh: async () => undefined,
  updateProfile: async () => baseUser,
  deleteAccount: async () => undefined,
  revokeAllSessions: async () => undefined,
});

const adminTree = (
  <MemoryRouter initialEntries={['/admin']}>
    <Routes>
      <Route path="/" element={<div>Accueil public</div>} />
      <Route path="/login" element={<div>Connexion</div>} />
      <Route
        path="/admin"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <div>Administration</div>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
    </Routes>
  </MemoryRouter>
);

const renderWithAuth = (status: React.ContextType<typeof AuthContext>['status'], user: AuthUser | null) =>
  render(
    <AuthContext.Provider value={authValue(status, user)}>{adminTree}</AuthContext.Provider>,
  );

describe('route guards', () => {
  it('redirects anonymous users to login before admin checks', () => {
    renderWithAuth('unauthenticated', null);

    expect(screen.getByText('Connexion')).toBeTruthy();
  });

  it('redirects authenticated users without admin permission away from admin', () => {
    renderWithAuth('authenticated', baseUser);

    expect(screen.getByText('Accueil public')).toBeTruthy();
  });

  it('allows users with admin access permission', () => {
    renderWithAuth('authenticated', {
      ...baseUser,
      permissions: ['admin.access'],
      roles: ['ROLE_ADMIN'],
    });

    expect(screen.getByText('Administration')).toBeTruthy();
  });

  it('keeps the protected route pending while the session is loading', () => {
    renderWithAuth('loading', null);

    expect(screen.getByText('Vérification de la session...')).toBeTruthy();
  });

  it('redirects to login when the session cannot be verified', () => {
    renderWithAuth('unavailable', null);

    expect(screen.getByText('Connexion')).toBeTruthy();
  });

  it('rejects admin access after the permission is revoked during the session', () => {
    const { rerender } = render(
      <AuthContext.Provider
        value={authValue('authenticated', {
          ...baseUser,
          permissions: ['admin.access'],
          roles: ['ROLE_ADMIN'],
        })}
      >
        {adminTree}
      </AuthContext.Provider>,
    );

    expect(screen.getByText('Administration')).toBeTruthy();

    rerender(<AuthContext.Provider value={authValue('authenticated', baseUser)}>{adminTree}</AuthContext.Provider>);

    expect(screen.getByText('Accueil public')).toBeTruthy();
  });
});
