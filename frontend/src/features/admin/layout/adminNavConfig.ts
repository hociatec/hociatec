import {
  Beaker,
  Calendar,
  Mail,
  Newspaper,
  Package,
  Settings,
  ShoppingCart,
  Users,
  Wrench,
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
      { to: '/admin/rentals', label: 'Locations' },
      { to: '/admin/orders/fulfillment', label: 'Expéditions' },
      { to: '/admin/payments', label: 'Paiements' },
      { to: '/admin/quotes', label: 'Devis' },
      { to: '/admin/trade-ins', label: 'Reprises matériel' },
    ],
  },
  {
    id: 'services',
    label: 'Services',
    icon: Wrench,
    links: [
      { to: '/admin/services', label: 'Lister les services' },
      { to: '/admin/services/new', label: 'Nouveau service' },
    ],
  },
  {
    id: 'appointments',
    label: 'Rendez-vous',
    icon: Calendar,
    links: [
      { to: '/admin/appointments/motifs', label: 'Motifs de rendez-vous' },
      { to: '/admin/appointments/motifs/new', label: 'Nouveau motif' },
      { to: '/admin/appointments/schedule', label: 'Planning RDV' },
    ],
  },
  {
    id: 'catalog',
    label: 'Catalogue',
    icon: Package,
    links: [
      { to: '/admin/catalog/products', label: 'Tous les produits' },
      { to: '/admin/catalog/stock', label: 'Stock' },
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
      { to: '/admin/customers/support', label: 'Demandes SAV' },
      { to: '/admin/customers/support/new', label: 'Créer un SAV' },
      { to: '/admin/customers/refunds', label: 'Demandes remboursement' },
      { to: '/admin/customers/refunds/new', label: 'Créer un remboursement' },
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
    id: 'trainings',
    label: 'Formations',
    icon: Calendar,
    links: [
      { to: '/admin/trainings', label: 'Catalogue formations' },
      { to: '/admin/trainings/sessions', label: 'Sessions' },
      { to: '/admin/trainings/enrollments', label: 'Inscriptions' },
    ],
  },
  {
    id: 'audits',
    label: 'Audits',
    icon: Calendar,
    links: [{ to: '/admin/audits', label: 'Demandes d’audit' }],
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
      { to: '/admin/transactional-emails/logs', label: 'Emails transactionnels' },
    ],
  },
  {
    id: 'system',
    label: 'Système',
    icon: Settings,
    links: [
      { to: '/admin/exports', label: 'Exports CSV' },
      { to: '/admin/backups', label: 'Sauvegardes et maintenance' },
      { to: '/admin/ui-catalog', label: 'Catalogue UI' },
    ],
  },
];
