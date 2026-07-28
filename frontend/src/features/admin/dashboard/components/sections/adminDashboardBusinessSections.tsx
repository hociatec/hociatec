import { adminDashboardIcons } from './adminDashboardSectionIcons';
import type { AdminDashboardSection } from './adminDashboardSectionTypes';

export const adminDashboardBusinessSections: AdminDashboardSection[] = [
  {
    id: 'promotions',
    title: 'Promotions',
    subtitle: 'Remises automatiques panier et accès aux bons de réduction séparés.',
    links: [
      {
        to: '/admin/promotions',
        title: 'Lister les promotions',
        icon: adminDashboardIcons.badgePercent,
      },
      {
        to: '/admin/promotions/new',
        title: 'Créer une promotion',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/vouchers',
        title: 'Bons de réduction',
        icon: adminDashboardIcons.fileText,
      },
    ],
  },
  {
    id: 'devis',
    title: 'Devis',
    subtitle:
      'Création, suivi et gestion des devis. Les devis peuvent intégrer des services du catalogue.',
    links: [
      {
        to: '/admin/quotes',
        title: 'Lister les devis',
        icon: adminDashboardIcons.fileText,
      },
      {
        to: '/admin/quotes/new',
        title: 'Créer un devis manuel',
        icon: adminDashboardIcons.plus,
      },
    ],
  },
  {
    id: 'audits',
    title: 'Audits',
    subtitle: 'Suivez et évaluez les audits.',
    links: [
      {
        to: '/admin/audits',
        title: 'Lister les audits',
        icon: adminDashboardIcons.shieldCheck,
      },
    ],
  },
];
