// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router';

import { AuthContext } from '@/features/auth/publicApi';
import { ProfilePage } from '@/features/profile/pages/ProfilePage';
import type { AuthUser } from '@/shared/types/auth';
import { ProfileEditDialog } from './ProfileEditDialog';
import { ProfileInformationCard } from './ProfileInformationCard';
import type { ProfileFormState } from '../hooks/useProfileController';

const user: AuthUser = {
  id: 1,
  email: 'client@hociatec.fr',
  firstName: 'Client',
  lastName: 'Hociatec',
  address: '',
  postalCode: '',
  city: '',
  birthDate: '1990-01-01',
  phoneNumber: '0600000000',
  gender: 'homme',
  roles: ['ROLE_USER'],
  permissions: [],
};

const form: ProfileFormState = {
  firstName: user.firstName,
  lastName: user.lastName,
  email: user.email,
  birthDate: user.birthDate,
  phoneNumber: user.phoneNumber,
  gender: user.gender,
  password: '',
  currentPassword: '',
};

const authValue = {
  user,
  status: 'authenticated' as const,
  login: async () => undefined,
  logout: async () => undefined,
  refresh: async () => undefined,
  updateProfile: async () => user,
  deleteAccount: async () => undefined,
};

afterEach(() => {
  cleanup();
});

class IntersectionObserverStub implements IntersectionObserver {
  readonly root = null;
  readonly rootMargin = '';
  readonly thresholds = [];

  disconnect(): void {}
  observe(): void {}
  takeRecords(): IntersectionObserverEntry[] { return []; }
  unobserve(): void {}
}

if (typeof window !== 'undefined' && !('IntersectionObserver' in window)) {
  Object.defineProperty(window, 'IntersectionObserver', {
    configurable: true,
    writable: true,
    value: IntersectionObserverStub,
  });
}

if (typeof globalThis !== 'undefined' && !('IntersectionObserver' in globalThis)) {
  Object.defineProperty(globalThis, 'IntersectionObserver', {
    configurable: true,
    writable: true,
    value: IntersectionObserverStub,
  });
}

describe('profile modal behavior', () => {
  it('keeps the profile information card read-only', () => {
    const onStartEditing = vi.fn();

    render(
      <ProfileInformationCard
        user={user}
        formattedBirthDate="1 janvier 1990"
        formattedRoles="Utilisateur"
        onStartEditing={onStartEditing}
      />,
    );

    expect(screen.queryByRole('form')).toBeNull();
    expect(screen.queryByLabelText('Prénom')).toBeNull();

    fireEvent.click(screen.getByRole('button', { name: 'Modifier' }));

    expect(onStartEditing).toHaveBeenCalledOnce();
  });

  it('renders profile edition in a blocking dialog', () => {
    render(
      <ProfileEditDialog
        feedback={null}
        form={form}
        hasCurrentPasswordRequirement={false}
        isSaving={false}
        onCancel={() => undefined}
        onFieldChange={() => undefined}
        onSubmit={() => undefined}
      />,
    );

    expect(screen.getByRole('dialog', { name: 'Modifier le profil' })).toBeTruthy();
    expect(screen.getByLabelText('Prénom')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Enregistrer' })).toBeTruthy();
  });

  it('opens the profile dialog from the real profile page action', async () => {
    render(
      <MemoryRouter>
        <AuthContext.Provider value={authValue}>
          <ProfilePage />
        </AuthContext.Provider>
      </MemoryRouter>,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Modifier' }));

    expect(screen.getByRole('dialog', { name: 'Modifier le profil' })).toBeTruthy();
    await waitFor(() => {
      expect(document.activeElement).toBe(screen.getByLabelText('Prénom'));
    });
  });
});
