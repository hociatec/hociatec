import { adminDashboardIcons } from './adminDashboardSectionIcons';
import type { AdminDashboardSection } from './adminDashboardSectionTypes';

export const adminDashboardCoreSections: AdminDashboardSection[] = [
  {
    id: 'home',
    title: 'Accueil',
    subtitle: 'Vue globale, indicateurs d’exploitation et raccourcis utiles.',
    links: [],
  },
  {
    id: 'commandes',
    title: 'Commandes',
    subtitle: 'Gérez les commandes clients et leurs statuts.',
    links: [
      {
        to: '/admin/orders',
        title: 'Lister les commandes',
        icon: adminDashboardIcons.package,
      },
      {
        to: '/admin/payments',
        title: 'Suivi des paiements',
        icon: adminDashboardIcons.creditCard,
      },
    ],
  },
  {
    id: 'exploitation',
    title: 'Exploitation',
    subtitle:
      'SAV, remboursements, stock, exports, emails, actions groupées et conversion des devis.',
    links: [
      {
        to: '/admin/operations',
        title: 'Centre exploitation',
        icon: adminDashboardIcons.wrench,
      },
    ],
  },
  {
    id: 'sauvegardes',
    title: 'Sauvegardes',
    subtitle: 'Planification des sauvegardes, historique, rétention et mode maintenance.',
    links: [
      {
        to: '/admin/backups',
        title: 'Gérer les sauvegardes et la maintenance',
        icon: adminDashboardIcons.databaseBackup,
      },
    ],
  },
  {
    id: 'clients',
    title: 'Clients',
    subtitle: 'Retrouvez vos clients, leurs commandes, leurs adresses et leur valeur.',
    links: [
      {
        to: '/admin/customers',
        title: 'Lister les clients',
        icon: adminDashboardIcons.users,
      },
    ],
  },
];
