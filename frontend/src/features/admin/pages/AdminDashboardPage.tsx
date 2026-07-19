import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowRight,
  BadgePercent,
  CalendarDays,
  CircleAlert,
  CircleCheckBig,
  FileText,
  House,
  Layers3,
  Mail,
  Package,
  CreditCard,
  Plus,
  ShieldCheck,
  Wrench,
  Users,
} from 'lucide-react';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { fetchAdminDashboard, type AdminDashboardDto } from '@/features/admin/customers/api';
import { formatOrderStatusFr, formatPaymentStatusFr, formatStripeFailureCodeFr, formatStripePaymentStatusFr } from '@/features/orders/api';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

interface SectionLink {
  to: string;
  title: string;
  icon?: React.ReactNode;
}

interface Section {
  id: string;
  title: string;
  subtitle: string;
  links: SectionLink[];
}

const ADMIN_DEFAULT_TAB_KEY = 'hociatec.admin.dashboard.defaultTab';

const readDefaultAdminTab = () => {
  if (typeof window === 'undefined') return null;
  try {
    return window.localStorage.getItem(ADMIN_DEFAULT_TAB_KEY);
  } catch {
    return null;
  }
};

const writeDefaultAdminTab = (value: string) => {
  if (typeof window === 'undefined') return;
  try {
    window.localStorage.setItem(ADMIN_DEFAULT_TAB_KEY, value);
  } catch {
    /* noop */
  }
};

const sections: Section[] = [
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
        icon: <Package className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/payments',
        title: 'Suivi des paiements',
        icon: <CreditCard className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'exploitation',
    title: 'Exploitation',
    subtitle: 'SAV, remboursements, stock, exports, emails, actions groupées et conversion des devis.',
    links: [
      {
        to: '/admin/operations',
        title: 'Centre exploitation',
        icon: <Wrench className="h-6 w-6 text-brand-400" />,
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
        icon: <Users className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'prestations',
    title: 'Rendez-vous et prestations',
    subtitle: 'Prestations réservables, durées, tarifs et créneaux de rendez-vous.',
    links: [
      {
        to: '/admin/appointments/prestations',
        title: 'Prestations de rendez-vous',
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/appointments/prestations/new',
        title: 'Ajouter une prestation',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/appointments/schedule',
        title: 'Configurer les créneaux',
        icon: <CalendarDays className="h-6 w-6 text-brand-400" />,
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
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/categories/new',
        title: 'Ajouter une catégorie',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/brands',
        title: 'Lister les marques',
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/brands/new',
        title: 'Ajouter une marque',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/products',
        title: 'Lister les produits',
        icon: <Package className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/products/new',
        title: 'Ajouter un produit',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
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
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/services/new',
        title: 'Nouveau service',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/services',
        title: 'Voir la page services',
        icon: <ArrowRight className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'marketing',
    title: 'Marketing',
    subtitle: 'Campagnes e-mail ciblées, modèles par scénario et relances clients.',
    links: [
      {
        to: '/admin/marketing',
        title: 'Campagnes e-mail',
        icon: <Mail className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/marketing/templates',
        title: 'Modèles d’e-mail',
        icon: <FileText className="h-6 w-6 text-brand-400" />,
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
        icon: <Mail className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/transactional-emails/new',
        title: 'Nouveau modèle transactionnel',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'promotions',
    title: 'Promotions',
    subtitle: 'Remises automatiques panier et accès aux bons de réduction séparés.',
    links: [
      {
        to: '/admin/promotions',
        title: 'Lister les promotions',
        icon: <BadgePercent className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/promotions/new',
        title: 'Créer une promotion',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/vouchers',
        title: 'Bons de réduction',
        icon: <FileText className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'devis',
    title: 'Devis',
    subtitle: 'Création, suivi et gestion des devis. Les devis peuvent intégrer des services du catalogue.',
    links: [
      {
        to: '/admin/quotes',
        title: 'Lister les devis',
        icon: <FileText className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/devis/nouveau',
        title: 'Créer un devis (espace client)',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
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
        icon: <ShieldCheck className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
];

export const AdminDashboardPage = () => {
  useDocumentTitle('Administration');
  const [defaultSection, setDefaultSection] = useState<string>(sections[0]?.id ?? 'home');
  const [savedMessage, setSavedMessage] = useState<string | null>(null);
  const [dashboard, setDashboard] = useState<AdminDashboardDto | null>(null);
  const [dashboardStatus, setDashboardStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [dashboardError, setDashboardError] = useState<string | null>(null);

  useEffect(() => {
    const saved = readDefaultAdminTab();
    if (saved && sections.some((section) => section.id === saved)) {
      setDefaultSection(saved);
    }
  }, []);

  useEffect(() => {
    setDashboardStatus('loading');
    setDashboardError(null);
    void fetchAdminDashboard()
      .then((data) => {
        setDashboard(data);
        setDashboardStatus('success');
      })
      .catch((error: unknown) => {
        setDashboardStatus('error');
        setDashboardError(error instanceof Error ? error.message : 'Impossible de charger le dashboard.');
      });
  }, []);

  const sectionTitleMap = useMemo(
    () => Object.fromEntries(sections.map((section) => [section.id, section.title])),
    [],
  );

  const formatPrice = (valueInCents: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

  return (
    <section className="mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 py-12">
      <header className="mx-auto max-w-3xl text-center">
        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">
          Espace administration
        </p>
        <h1 className="mt-3 text-4xl font-bold text-white sm:text-5xl">
          Tableau de bord
        </h1>
        <p className="mt-5 text-base text-slate-300">
          Centralisez la gestion de votre activité, suivez les signaux utiles et accédez rapidement aux zones d’action.
        </p>
      </header>

      <Tabs
        defaultValue={sections[0]?.id ?? 'home'}
        value={defaultSection}
        onValueChange={setDefaultSection}
        className="w-full"
      >
        <TabsList className="mx-auto mb-10 grid w-full max-w-5xl grid-cols-1 gap-3 sm:grid-cols-4 lg:grid-cols-6">
          {sections.map((section) => (
            <TabsTrigger
              key={section.id}
              value={section.id}
              className="rounded-xl bg-slate-800/60 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-700/70 data-[state=active]:bg-brand-600 data-[state=active]:text-white"
            >
              {section.title}
            </TabsTrigger>
          ))}
        </TabsList>

        {sections.map((section) => (
          <TabsContent key={section.id} value={section.id} className="flex flex-col gap-10">
            <div className="text-center">
              <h2 className="text-2xl font-semibold text-white">{section.title}</h2>
              <p className="mt-2 text-slate-400">{section.subtitle}</p>
            </div>

            {section.id === 'home' && (
              <div className="space-y-6">
                <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6">
                  <div className="mb-4 flex items-center gap-3">
                    <House className="h-6 w-6 text-brand-400" />
                    <h3 className="text-lg font-semibold text-white">Onglet par défaut</h3>
                  </div>
                  <p className="mb-4 text-sm text-slate-400">
                    Choisissez l’onglet affiché automatiquement à l’ouverture du dashboard admin sur ce navigateur.
                  </p>
                  <select
                    className="register-form__input"
                    value={defaultSection}
                    onChange={(event) => {
                      const next = event.target.value;
                      setDefaultSection(next);
                      writeDefaultAdminTab(next);
                      setSavedMessage(`Onglet par défaut enregistré: ${sectionTitleMap[next] ?? next}.`);
                    }}
                  >
                    {sections.map((item) => (
                      <option key={item.id} value={item.id}>
                        {item.title}
                      </option>
                    ))}
                  </select>
                  {savedMessage && <p className="mt-3 text-sm text-emerald-300">{savedMessage}</p>}
                </div>

                {dashboardStatus === 'loading' && (
                  <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6 text-sm text-slate-300">
                    Chargement des indicateurs...
                  </div>
                )}

                {dashboardError && (
                  <div className="rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
                    {dashboardError}
                  </div>
                )}

                {dashboard && (
                  <>
                    <section className="space-y-4">
                      <div>
                        <h3 className="text-lg font-semibold text-white">Vue d’ensemble</h3>
                        <p className="text-sm text-slate-400">Volumes, chiffre d’affaires et base clients.</p>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5">
                          <div className="text-sm text-slate-400">Aujourd’hui</div>
                          <div className="mt-2 text-3xl font-semibold text-white">{dashboard.metrics.today.count}</div>
                          <div className="mt-1 text-sm text-slate-300">{formatPrice(dashboard.metrics.today.totalCents)}</div>
                        </div>
                        <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5">
                          <div className="text-sm text-slate-400">Cette semaine</div>
                          <div className="mt-2 text-3xl font-semibold text-white">{dashboard.metrics.week.count}</div>
                          <div className="mt-1 text-sm text-slate-300">{formatPrice(dashboard.metrics.week.totalCents)}</div>
                        </div>
                        <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5">
                          <div className="text-sm text-slate-400">Ce mois</div>
                          <div className="mt-2 text-3xl font-semibold text-white">{dashboard.metrics.month.count}</div>
                          <div className="mt-1 text-sm text-slate-300">{formatPrice(dashboard.metrics.month.totalCents)}</div>
                        </div>
                        <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5">
                          <div className="text-sm text-slate-400">Base clients</div>
                          <div className="mt-2 text-3xl font-semibold text-white">{dashboard.metrics.customersCount}</div>
                          <div className="mt-1 text-sm text-slate-300">{dashboard.topCustomers.length} clients mis en avant</div>
                        </div>
                      </div>
                    </section>

                    <section className="space-y-4">
                      <div>
                        <h3 className="text-lg font-semibold text-white">Actions prioritaires</h3>
                        <p className="text-sm text-slate-400">Les points qui demandent une action immédiate.</p>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <Link to="/admin/orders?status=pending" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Commandes à traiter</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.metrics.statusCounts.pending ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Ouvrir la liste</div>
                        </Link>
                        <Link to="/admin/orders?status=confirmed" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Commandes confirmées</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.metrics.statusCounts.confirmed ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Ouvrir la liste</div>
                        </Link>
                        <Link to="/admin/orders?status=delivered" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Commandes livrées</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.metrics.statusCounts.delivered ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Ouvrir la liste</div>
                        </Link>
                        <Link to="/admin/orders?health=issues" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Incidents de traitement</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.metrics.issuesCount}</div>
                          <div className="mt-2 text-xs text-brand-300">Traiter maintenant</div>
                        </Link>
                        <Link to="/admin/catalog/products?stock=low" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Stocks faibles</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.metrics.lowStockCount}</div>
                          <div className="mt-2 text-xs text-brand-300">Voir les produits</div>
                        </Link>
                        <Link to="/admin/operations" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">SAV / remboursements</div>
                          <div className="mt-2 text-2xl font-semibold text-white">
                            {(dashboard.metrics.supportOpenCount ?? 0) + (dashboard.metrics.refundsPendingCount ?? 0)}
                          </div>
                          <div className="mt-2 text-xs text-brand-300">Ouvrir exploitation</div>
                        </Link>
                      </div>
                    </section>

                    <section className="space-y-4">
                      <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                          <h3 className="text-lg font-semibold text-white">Notifications admin</h3>
                          <p className="text-sm text-slate-400">Devis acceptés, commandes à régler, emails et paiements récents.</p>
                        </div>
                        <Link to="/admin/quotes" className="text-sm font-medium text-brand-300 underline">
                          Voir les devis
                        </Link>
                      </div>
                      <div className="grid gap-3 xl:grid-cols-2">
                        {(dashboard.notifications ?? []).length === 0 ? (
                          <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 text-sm text-slate-300">
                            Aucune notification récente.
                          </div>
                        ) : (
                          (dashboard.notifications ?? []).map((item) => {
                            const isAction = item.severity === 'action';
                            const isDanger = item.severity === 'danger';
                            return (
                              <Link
                                key={item.id}
                                to={item.to}
                                className={`flex gap-3 rounded-2xl border p-4 transition hover:bg-slate-800/80 ${
                                  isDanger
                                    ? 'border-red-500/40 bg-red-500/10'
                                    : isAction
                                      ? 'border-amber-400/40 bg-amber-400/10'
                                      : 'border-slate-700 bg-slate-800/50'
                                }`}
                              >
                                <div className="mt-0.5">
                                  {isDanger ? (
                                    <CircleAlert className="h-5 w-5 text-red-300" />
                                  ) : isAction ? (
                                    <CircleAlert className="h-5 w-5 text-amber-300" />
                                  ) : (
                                    <CircleCheckBig className="h-5 w-5 text-emerald-300" />
                                  )}
                                </div>
                                <div className="min-w-0 flex-1">
                                  <div className="font-semibold text-white">{item.title}</div>
                                  <div className="mt-1 truncate text-sm text-slate-300">{item.message || item.type}</div>
                                  <div className="mt-2 text-xs text-slate-400">
                                    {new Date(item.createdAt).toLocaleString('fr-FR')}
                                  </div>
                                </div>
                                <ArrowRight className="mt-1 h-4 w-4 flex-none text-slate-500" />
                              </Link>
                            );
                          })
                        )}
                      </div>
                    </section>

                    <section className="space-y-4">
                      <div>
                        <h3 className="text-lg font-semibold text-white">Suivi des paiements</h3>
                        <p className="text-sm text-slate-400">Vue rapide des paiements confirmés, en attente et des cas à traiter.</p>
                      </div>
                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <Link to="/admin/payments?status=paid" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Paiements confirmés</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.payments.statusCounts.paid ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Voir les paiements</div>
                        </Link>
                        <Link to="/admin/payments?status=open" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Paiements en attente</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.payments.statusCounts.open ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Sessions ouvertes</div>
                        </Link>
                        <Link to="/admin/payments?status=failed" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Paiements échoués</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.payments.statusCounts.failed ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Analyser les refus</div>
                        </Link>
                        <Link to="/admin/payments?status=expired" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Paiements expirés</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.payments.statusCounts.expired ?? 0}</div>
                          <div className="mt-2 text-xs text-brand-300">Voir les sessions perdues</div>
                        </Link>
                        <Link to="/admin/payments" className="rounded-2xl border border-slate-700 bg-slate-800/50 p-5 transition hover:border-brand-500 hover:bg-slate-800/80">
                          <div className="text-sm text-slate-400">Payés sans commande liée</div>
                          <div className="mt-2 text-2xl font-semibold text-white">{dashboard.payments.paidWithoutOrderCount}</div>
                          <div className="mt-2 text-xs text-brand-300">Contrôler les incohérences</div>
                        </Link>
                      </div>
                    </section>

                    <section className="grid gap-6 xl:grid-cols-3">
                      <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6 xl:col-span-2">
                        <div className="mb-4 flex items-center justify-between gap-3">
                          <div>
                            <h3 className="text-lg font-semibold text-white">Suivi commandes</h3>
                            <p className="text-sm text-slate-400">Dernières commandes enregistrées.</p>
                          </div>
                          <Link to="/admin/orders" className="text-sm font-medium text-brand-300 underline">
                            Toutes les commandes
                          </Link>
                        </div>
                        <div className="space-y-3">
                          {dashboard.recentOrders.map((order) => (
                            <Link
                              key={order.id}
                              to={`/admin/orders/${order.id}`}
                              className="flex flex-col gap-2 rounded-2xl bg-slate-900/40 p-4 transition hover:bg-slate-900/70 md:flex-row md:items-center md:justify-between"
                            >
                              <div>
                                <div className="font-semibold text-white">{order.number}</div>
                                <div className="text-sm text-slate-300">
                                  {order.customerDisplayName} · {new Date(order.createdAt).toLocaleString('fr-FR')}
                                </div>
                              </div>
                              <div className="text-right">
                                <div className="text-sm font-semibold text-white">{formatPrice(order.totalPriceCents)}</div>
                                <div className="text-xs uppercase tracking-wide text-slate-400">
                                  {order.statusLabel ?? formatOrderStatusFr(order.status)}
                                </div>
                              </div>
                            </Link>
                          ))}
                        </div>
                      </div>

                      <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6">
                        <div className="mb-4 flex items-center justify-between gap-3">
                          <div>
                            <h3 className="text-lg font-semibold text-white">Clients à suivre</h3>
                            <p className="text-sm text-slate-400">Top clients par valeur.</p>
                          </div>
                          <Link to="/admin/customers" className="text-sm font-medium text-brand-300 underline">
                            Tous les clients
                          </Link>
                        </div>
                        <div className="space-y-3">
                          {dashboard.topCustomers.map((customer) => (
                            <Link
                              key={customer.id}
                              to={`/admin/customers/${customer.id}`}
                              className="block rounded-2xl bg-slate-900/40 p-4 transition hover:bg-slate-900/70"
                            >
                              <div className="font-semibold text-white">{customer.firstName} {customer.lastName}</div>
                              <div className="text-sm text-slate-300">{customer.email}</div>
                              <div className="mt-2 text-sm text-slate-200">
                                {customer.ordersCount} commande{customer.ordersCount > 1 ? 's' : ''} · {formatPrice(customer.totalSpentCents)}
                              </div>
                            </Link>
                          ))}
                        </div>
                      </div>
                    </section>

                    <section className="grid gap-6 xl:grid-cols-2">
                      <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6">
                        <div className="mb-4 flex items-center justify-between gap-3">
                          <div>
                            <h3 className="text-lg font-semibold text-white">Paiements à surveiller</h3>
                            <p className="text-sm text-slate-400">Échecs, expirations et paiements sans commande liée.</p>
                          </div>
                          <Link to="/admin/payments" className="text-sm font-medium text-brand-300 underline">
                            Ouvrir le module
                          </Link>
                        </div>
                        <div className="space-y-3">
                          {dashboard.payments.attention.length === 0 ? (
                            <div className="rounded-2xl bg-slate-900/40 p-4 text-sm text-slate-300">
                              Aucun paiement critique à traiter.
                            </div>
                          ) : dashboard.payments.attention.map((payment) => (
                            <Link
                              key={payment.id}
                              to={`/admin/payments/${payment.id}`}
                              className="flex flex-col gap-2 rounded-2xl bg-slate-900/40 p-4 transition hover:bg-slate-900/70 md:flex-row md:items-center md:justify-between"
                            >
                              <div className="min-w-0">
                                <div className="flex items-center gap-2 font-semibold text-white">
                                  <CircleAlert className="h-4 w-4 text-amber-300" />
                                  <span>{payment.customerFullName || payment.customerEmail}</span>
                                </div>
                                <div className="text-sm text-slate-300">
                                  {payment.statusLabel ?? formatPaymentStatusFr(payment.status)}
                                  {' · '}
                                  {payment.stripePaymentStatusLabel ?? formatStripePaymentStatusFr(payment.stripePaymentStatus)}
                                </div>
                                <div className="text-xs text-slate-400">
                                  {payment.failureMessage || (payment.failureCode ? formatStripeFailureCodeFr(payment.failureCode) : (payment.orderId === null && payment.status === 'paid' ? 'Paiement confirmé sans commande liée.' : 'À contrôler'))}
                                </div>
                              </div>
                              <div className="text-right">
                                <div className="text-sm font-semibold text-white">{formatPrice(payment.totalPriceCents)}</div>
                                <div className="text-xs text-slate-400">{new Date(payment.createdAt).toLocaleString('fr-FR')}</div>
                              </div>
                            </Link>
                          ))}
                        </div>
                      </div>

                      <div className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6">
                        <div className="mb-4 flex items-center justify-between gap-3">
                          <div>
                            <h3 className="text-lg font-semibold text-white">Derniers paiements</h3>
                            <p className="text-sm text-slate-400">Accès rapide aux dernières sessions de paiement.</p>
                          </div>
                          <Link to="/admin/payments" className="text-sm font-medium text-brand-300 underline">
                            Tous les paiements
                          </Link>
                        </div>
                        <div className="space-y-3">
                          {dashboard.payments.recent.map((payment) => (
                            <Link
                              key={payment.id}
                              to={`/admin/payments/${payment.id}`}
                              className="flex flex-col gap-2 rounded-2xl bg-slate-900/40 p-4 transition hover:bg-slate-900/70 md:flex-row md:items-center md:justify-between"
                            >
                              <div className="min-w-0">
                                <div className="flex items-center gap-2 font-semibold text-white">
                                  <CircleCheckBig className="h-4 w-4 text-emerald-300" />
                                  <span>{payment.customerFullName || payment.customerEmail}</span>
                                </div>
                                <div className="text-sm text-slate-300">
                                  {payment.statusLabel ?? formatPaymentStatusFr(payment.status)}
                                  {' · '}
                                  {payment.orderId ? `Commande #${payment.orderId}` : 'Aucune commande liée'}
                                </div>
                              </div>
                              <div className="text-right">
                                <div className="text-sm font-semibold text-white">{formatPrice(payment.totalPriceCents)}</div>
                                <div className="text-xs text-slate-400">{new Date(payment.createdAt).toLocaleString('fr-FR')}</div>
                              </div>
                            </Link>
                          ))}
                        </div>
                      </div>
                    </section>

                    <section className="rounded-2xl border border-slate-700 bg-slate-800/50 p-6">
                      <div className="mb-4">
                        <h3 className="text-lg font-semibold text-white">Journal récent</h3>
                        <p className="text-sm text-slate-400">Derniers événements enregistrés sur les commandes.</p>
                      </div>
                      <div className="space-y-3">
                        {dashboard.recentEvents.map((event) => (
                          <Link
                            key={event.id}
                            to={`/admin/orders/${event.order.id}`}
                            className="block rounded-2xl bg-slate-900/40 p-4 transition hover:bg-slate-900/70"
                          >
                            <div className="text-sm font-semibold text-white">{event.order.number}</div>
                            <div className="mt-1 text-sm text-slate-300">{event.message || event.type}</div>
                            <div className="mt-1 text-xs text-slate-400">
                              {new Date(event.createdAt).toLocaleString('fr-FR')}
                              {event.actor?.name ? ` · ${event.actor.name}` : ''}
                            </div>
                          </Link>
                        ))}
                      </div>
                    </section>
                  </>
                )}
              </div>
            )}

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {section.links.map((link) => (
                <Link
                  key={link.to}
                  to={link.to}
                  className="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/50 p-6 transition-all hover:-translate-y-1 hover:border-brand-500 hover:bg-slate-800/80"
                >
                  <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-700/50 group-hover:bg-brand-600/20">
                      {link.icon}
                    </div>
                    <span className="text-base font-semibold text-white group-hover:text-brand-300">
                      {link.title}
                    </span>
                  </div>
                  <ArrowRight className="absolute right-4 top-4 h-4 w-4 text-slate-500 opacity-0 transition-all group-hover:right-3 group-hover:opacity-100 group-hover:text-brand-400" />
                </Link>
              ))}
            </div>
          </TabsContent>
        ))}
      </Tabs>
    </section>
  );
};
