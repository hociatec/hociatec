import { adminDashboardIcons } from './adminDashboardSectionIcons';
import type { AdminDashboardSection } from './adminDashboardSectionTypes';

export const adminDashboardCatalogSections: AdminDashboardSection[] = [
  {
    id: 'prestations',
    title: 'Rendez-vous et prestations',
    subtitle: 'Prestations réservables, durées, tarifs et créneaux de rendez-vous.',
    links: [
      {
        to: '/admin/appointments/prestations',
        title: 'Prestations de rendez-vous',
        icon: adminDashboardIcons.layers,
      },
      {
        to: '/admin/appointments/prestations/new',
        title: 'Ajouter une prestation',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/appointments/schedule',
        title: 'Configurer les créneaux',
        icon: adminDashboardIcons.calendarDays,
      },
    ],
  },
  {
    id: 'catalogue',
    title: 'Catalogue produits',
    subtitle: 'Gérez vos catégories, vos marques et vos produits.',
    links: [
      {
        to: '/admin/catalog/categories',
        title: 'Lister les catégories',
        icon: adminDashboardIcons.layers,
      },
      {
        to: '/admin/catalog/categories/new',
        title: 'Ajouter une catégorie',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/catalog/brands',
        title: 'Lister les marques',
        icon: adminDashboardIcons.layers,
      },
      {
        to: '/admin/catalog/brands/new',
        title: 'Ajouter une marque',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/catalog/products',
        title: 'Lister les produits',
        icon: adminDashboardIcons.package,
      },
      {
        to: '/admin/catalog/products/new',
        title: 'Ajouter un produit',
        icon: adminDashboardIcons.plus,
      },
    ],
  },
  {
    id: 'services',
    title: 'Services',
    subtitle: 'Catalogue autonome des offres et interventions proposées par Hociatec.',
    links: [
      {
        to: '/admin/services',
        title: 'Catalogue de services',
        icon: adminDashboardIcons.layers,
      },
      {
        to: '/admin/services/new',
        title: 'Nouveau service',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/services',
        title: 'Voir la page services',
        icon: adminDashboardIcons.arrowRight,
      },
    ],
  },
];
