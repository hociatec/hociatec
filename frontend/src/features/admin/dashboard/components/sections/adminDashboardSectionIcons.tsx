import type { LucideIcon } from 'lucide-react';
import {
  ArrowRight,
  BadgePercent,
  CalendarDays,
  CreditCard,
  DatabaseBackup,
  FileText,
  GraduationCap,
  Layers3,
  Mail,
  Package,
  Plus,
  ShieldCheck,
  Users,
  Wrench,
} from 'lucide-react';

export const sectionIcon = (Icon: LucideIcon) => <Icon className="h-6 w-6 text-brand-400" />;

export const adminDashboardIcons = {
  arrowRight: sectionIcon(ArrowRight),
  badgePercent: sectionIcon(BadgePercent),
  calendarDays: sectionIcon(CalendarDays),
  creditCard: sectionIcon(CreditCard),
  databaseBackup: sectionIcon(DatabaseBackup),
  fileText: sectionIcon(FileText),
  graduationCap: sectionIcon(GraduationCap),
  layers: sectionIcon(Layers3),
  mail: sectionIcon(Mail),
  package: sectionIcon(Package),
  plus: sectionIcon(Plus),
  shieldCheck: sectionIcon(ShieldCheck),
  users: sectionIcon(Users),
  wrench: sectionIcon(Wrench),
};
