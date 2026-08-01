import {
  Beaker,
  Calendar,
  Mail,
  Newspaper,
  Package,
  Settings,
  ShoppingCart,
  Users,
  type LucideIcon,
} from 'lucide-react';

export type AdminNavLink = {
  to: string;
  label: string;
  match?: string[];
};

export type AdminNavGroup = {
  id: string;
  label: string;
  icon: LucideIcon;
  links: AdminNavLink[];
};

export const adminNavGroups: AdminNavGroup[] = [
  {
    id: 'sales',
    label: 'Ventes',
    icon: ShoppingCart,
    links: [
      { to: '/admin/orders', label: 'Commandes' },
      { to: '/admin/payments', label: 'Paiements' },
      { to: '/admin/quotes', label: 'Devis' },
      { to: '/admin/services', label: 'Services' },
      { to: '/admin/trade-ins', label: 'Reprises matériel' },
    ],
  },
  {
    id: 'catalog',
    label: 'Catalogue',
    icon: Package,
    links: [
      { to: '/admin/catalog/products', label: 'Tous les produits' },
      { to: '/admin/catalog/categories', label: 'Catégories' },
      { to: '/admin/catalog/brands', label: 'Marques' },
      { to: '/admin/promotions', label: 'Promotions' },
      { to: '/admin/vouchers', label: 'Bons de réduction' },
    ],
  },
  {
    id: 'customers',
    label: 'Relation client',
    icon: Users,
    links: [
      { to: '/admin/customers', label: 'Liste des clients' },
      { to: '/admin/loyalty', label: 'Fidélité' },
    ],
  },
  {
    id: 'news',
    label: 'Actualités',
    icon: Newspaper,
    links: [
      { to: '/admin/news', label: 'Toutes les actualités' },
      { to: '/admin/news/new', label: 'Nouvelle actualité' },
    ],
  },
  {
    id: 'prestations_formations',
    label: 'Prestations & Formations',
    icon: Calendar,
    links: [
      { to: '/admin/appointments/prestations', label: 'Prestations RDV' },
      { to: '/admin/appointments/schedule', label: 'Planning RDV' },
      { to: '/admin/trainings', label: 'Formations' },
      { to: '/admin/trainings/sessions', label: 'Sessions' },
      { to: '/admin/trainings/enrollments', label: 'Inscriptions' },
      { to: '/admin/audits', label: 'Audits' },
    ],
  },
  {
    id: 'beta_program',
    label: 'Programme Bêta',
    icon: Beaker,
    links: [
      { to: '/admin/beta-campaigns', label: 'Campagnes bêta' },
      { to: '/admin/beta-testers', label: 'Bêta-testeurs' },
      { to: '/admin/beta-reports', label: 'Signalements de bugs' },
    ],
  },
  {
    id: 'marketing',
    label: 'Marketing',
    icon: Mail,
    links: [
      { to: '/admin/marketing', label: 'Campagnes' },
      { to: '/admin/marketing/templates', label: 'Modèles e-mail' },
    ],
  },
  {
    id: 'system',
    label: 'Système',
    icon: Settings,
    links: [
      { to: '/admin/operations', label: 'Opérations' },
      { to: '/admin/backups', label: 'Sauvegardes et maintenance' },
    ],
  },
];
