export type ProfileFeedback = {
  type: 'success' | 'error';
  message: string;
  details?: string[];
} | null;

export const PASSWORD_RULE = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

export const normalizeEmail = (email: string) => email.trim().toLowerCase();

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

export const extractErrorDetails = (error: unknown): string[] => {
  if (error && typeof error === 'object' && 'details' in error) {
    const details = (error as { details?: unknown }).details;
    if (Array.isArray(details)) return details.map((detail) => String(detail));
  }

  return [];
};

export const extractErrorMessage = (error: unknown, fallback: string) =>
  error instanceof Error ? error.message : fallback;
