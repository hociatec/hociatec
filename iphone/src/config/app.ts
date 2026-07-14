import Constants from 'expo-constants';

const expoExtra =
  (Constants.expoConfig?.extra as
    | {
        apiBaseUrl?: string;
        websiteUrl?: string;
      }
    | undefined) ?? {};

export const WEBSITE_URL = expoExtra.websiteUrl ?? 'https://hociatec.fr';
export const API_BASE_URL = expoExtra.apiBaseUrl ?? 'https://api.hociatec.fr';

export const APP_SECTIONS = [
  {
    title: 'Vente',
    text: 'Matériel neuf et reconditionné avec des fiches produits claires et exploitables sur mobile.',
  },
  {
    title: 'Location',
    text: 'Parc temporaire, postes configurés et accompagnement pour les besoins ponctuels ou récurrents.',
  },
  {
    title: 'Services',
    text: 'Catalogue de services Hociatec avec détails, durée et modalités d’intervention.',
  },
  {
    title: 'Compte client',
    text: 'Accès au profil, aux devis, aux commandes et aux rendez-vous depuis une interface mobile dédiée.',
  },
] as const;
