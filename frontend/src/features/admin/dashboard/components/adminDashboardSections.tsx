import { adminDashboardBusinessSections } from './sections/adminDashboardBusinessSections';
import { adminDashboardCatalogSections } from './sections/adminDashboardCatalogSections';
import { adminDashboardCoreSections } from './sections/adminDashboardCoreSections';
import { adminDashboardLearningSections } from './sections/adminDashboardLearningSections';
import { adminDashboardMarketingSections } from './sections/adminDashboardMarketingSections';
import type {
  AdminDashboardSection,
  AdminDashboardSectionLink,
} from './sections/adminDashboardSectionTypes';

export type { AdminDashboardSection, AdminDashboardSectionLink };

export const adminDashboardSections: AdminDashboardSection[] = [
  ...adminDashboardCoreSections,
  ...adminDashboardCatalogSections,
  ...adminDashboardLearningSections,
  ...adminDashboardMarketingSections,
  ...adminDashboardBusinessSections,
];
