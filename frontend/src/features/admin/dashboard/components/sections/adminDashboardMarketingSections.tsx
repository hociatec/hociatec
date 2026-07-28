import { adminDashboardIcons } from './adminDashboardSectionIcons';
import type { AdminDashboardSection } from './adminDashboardSectionTypes';

export const adminDashboardMarketingSections: AdminDashboardSection[] = [
  {
    id: 'marketing',
    title: 'Marketing',
    subtitle: 'Campagnes e-mail ciblées, modèles par scénario et relances clients.',
    links: [
      {
        to: '/admin/marketing',
        title: 'Campagnes e-mail',
        icon: adminDashboardIcons.mail,
      },
      {
        to: '/admin/marketing/templates',
        title: 'Modèles d’e-mail',
        icon: adminDashboardIcons.fileText,
      },
    ],
  },
  {
    id: 'transactional-emails',
    title: 'E-mails transactionnels',
    subtitle: 'Gérez les e-mails automatiques liés aux commandes et aux factures.',
    links: [
      {
        to: '/admin/transactional-emails',
        title: 'Bibliothèque des e-mails transactionnels',
        icon: adminDashboardIcons.mail,
      },
      {
        to: '/admin/transactional-emails/new',
        title: 'Nouveau modèle transactionnel',
        icon: adminDashboardIcons.plus,
      },
    ],
  },
];
