import { adminDashboardIcons } from './adminDashboardSectionIcons';
import type { AdminDashboardSection } from './adminDashboardSectionTypes';

export const adminDashboardLearningSections: AdminDashboardSection[] = [
  {
    id: 'formations',
    title: 'Formations',
    subtitle:
      'Formations payantes, feuilles de route, sessions en présentiel ou distanciel et inscriptions.',
    links: [
      {
        to: '/admin/trainings',
        title: 'Gérer les formations',
        icon: adminDashboardIcons.graduationCap,
      },
      {
        to: '/admin/trainings/new',
        title: 'Nouvelle formation',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/trainings/sessions',
        title: 'Gérer les sessions',
        icon: adminDashboardIcons.calendarDays,
      },
      {
        to: '/admin/trainings/sessions/new',
        title: 'Nouvelle session',
        icon: adminDashboardIcons.plus,
      },
      {
        to: '/admin/trainings/enrollments',
        title: 'Suivre les inscriptions',
        icon: adminDashboardIcons.users,
      },
    ],
  },
];
