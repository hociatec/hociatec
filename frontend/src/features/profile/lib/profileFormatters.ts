export type ProfileFeedback = {
  type: 'success' | 'error';
  message: string;
  details?: string[];
} | null;

export const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

import { normalizeSearchText } from '@/shared/lib/searchText';

export const normalizeEmail = (email: string) => normalizeSearchText(email).trim();

export const formatRole = (role: string) => {
  switch (role) {
    case 'ROLE_ADMIN':
      return 'Administrateur';
    case 'ROLE_MANAGER':
      return 'Manager';
    default:
      return 'Utilisateur';
  }
};

export const formatGender = (gender: string) => {
  switch (gender) {
    case 'homme':
      return 'Homme';
    case 'femme':
      return 'Femme';
    case 'autre':
      return 'Autre';
    default:
      return 'Non renseigné';
  }
};
