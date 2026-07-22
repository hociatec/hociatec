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

export interface AdminDashboardSectionLink {
  to: string;
  title: string;
  icon?: React.ReactNode;
}

export interface AdminDashboardSection {
  id: string;
  title: string;
  subtitle: string;
  links: AdminDashboardSectionLink[];
}

export const adminDashboardSections: AdminDashboardSection[] = [
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
      { to: '/admin/orders', title: 'Lister les commandes', icon: <Package className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/payments', title: 'Suivi des paiements', icon: <CreditCard className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'exploitation',
    title: 'Exploitation',
    subtitle: 'SAV, remboursements, stock, exports, emails, actions groupées et conversion des devis.',
    links: [
      { to: '/admin/operations', title: 'Centre exploitation', icon: <Wrench className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'sauvegardes',
    title: 'Sauvegardes',
    subtitle: 'Planification des sauvegardes, historique, rétention et mode maintenance.',
    links: [
      { to: '/admin/backups', title: 'Gérer les sauvegardes et la maintenance', icon: <DatabaseBackup className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'clients',
    title: 'Clients',
    subtitle: 'Retrouvez vos clients, leurs commandes, leurs adresses et leur valeur.',
    links: [
      { to: '/admin/customers', title: 'Lister les clients', icon: <Users className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'prestations',
    title: 'Rendez-vous et prestations',
    subtitle: 'Prestations réservables, durées, tarifs et créneaux de rendez-vous.',
    links: [
      { to: '/admin/appointments/prestations', title: 'Prestations de rendez-vous', icon: <Layers3 className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/appointments/prestations/new', title: 'Ajouter une prestation', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/appointments/schedule', title: 'Configurer les créneaux', icon: <CalendarDays className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'catalogue',
    title: 'Catalogue produits',
    subtitle: 'Gérez vos catégories, vos marques et vos produits.',
    links: [
      { to: '/admin/catalog/categories', title: 'Lister les catégories', icon: <Layers3 className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/catalog/categories/new', title: 'Ajouter une catégorie', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/catalog/brands', title: 'Lister les marques', icon: <Layers3 className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/catalog/brands/new', title: 'Ajouter une marque', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/catalog/products', title: 'Lister les produits', icon: <Package className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/catalog/products/new', title: 'Ajouter un produit', icon: <Plus className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'services',
    title: 'Services',
    subtitle: 'Catalogue autonome des offres et interventions proposées par Hociatec.',
    links: [
      { to: '/admin/services', title: 'Catalogue de services', icon: <Layers3 className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/services/new', title: 'Nouveau service', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/services', title: 'Voir la page services', icon: <ArrowRight className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'formations',
    title: 'Formations',
    subtitle: 'Formations payantes, feuilles de route, sessions en présentiel ou distanciel et inscriptions.',
    links: [
      { to: '/admin/trainings', title: 'Gérer les formations', icon: <GraduationCap className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/trainings/new', title: 'Nouvelle formation', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/trainings/sessions', title: 'Gérer les sessions', icon: <CalendarDays className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/trainings/sessions/new', title: 'Nouvelle session', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/trainings/enrollments', title: 'Suivre les inscriptions', icon: <Users className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'marketing',
    title: 'Marketing',
    subtitle: 'Campagnes e-mail ciblées, modèles par scénario et relances clients.',
    links: [
      { to: '/admin/marketing', title: 'Campagnes e-mail', icon: <Mail className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/marketing/templates', title: 'Modèles d’e-mail', icon: <FileText className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'transactional-emails',
    title: 'E-mails transactionnels',
    subtitle: 'Gérez les e-mails automatiques liés aux commandes et aux factures.',
    links: [
      { to: '/admin/transactional-emails', title: 'Bibliothèque des e-mails transactionnels', icon: <Mail className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/transactional-emails/new', title: 'Nouveau modèle transactionnel', icon: <Plus className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'promotions',
    title: 'Promotions',
    subtitle: 'Remises automatiques panier et accès aux bons de réduction séparés.',
    links: [
      { to: '/admin/promotions', title: 'Lister les promotions', icon: <BadgePercent className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/promotions/new', title: 'Créer une promotion', icon: <Plus className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/vouchers', title: 'Bons de réduction', icon: <FileText className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'devis',
    title: 'Devis',
    subtitle: 'Création, suivi et gestion des devis. Les devis peuvent intégrer des services du catalogue.',
    links: [
      { to: '/admin/quotes', title: 'Lister les devis', icon: <FileText className="h-6 w-6 text-brand-400" /> },
      { to: '/admin/quotes/new', title: 'Créer un devis manuel', icon: <Plus className="h-6 w-6 text-brand-400" /> },
    ],
  },
  {
    id: 'audits',
    title: 'Audits',
    subtitle: 'Suivez et évaluez les audits.',
    links: [
      { to: '/admin/audits', title: 'Lister les audits', icon: <ShieldCheck className="h-6 w-6 text-brand-400" /> },
    ],
  },
];
