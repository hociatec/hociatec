import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, BadgePercent, CalendarDays, FileText, House, Layers3, Mail, Package, Plus, ShieldCheck } from 'lucide-react';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
    subtitle: 'Point d’entrée admin et réglages rapides.',
    links: [
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
        title: 'Catalogue services',
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
    subtitle: 'Campagnes email ciblées, templates par scénario et relances clients.',
    links: [
      {
        to: '/admin/marketing',
        title: 'Campagnes email',
        icon: <Mail className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/marketing/templates',
        title: 'Templates email',
        icon: <FileText className="h-6 w-6 text-brand-400" />,
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
    ],
  }
];

export const AdminDashboardPage = () => {
  useDocumentTitle('Administration');
  const [defaultSection, setDefaultSection] = useState<string>(sections[0]?.id ?? 'home');
  const [savedMessage, setSavedMessage] = useState<string | null>(null);

  useEffect(() => {
    const saved = readDefaultAdminTab();
    if (saved && sections.some((section) => section.id === saved)) {
      setDefaultSection(saved);
    }
  }, []);

  const sectionTitleMap = useMemo(
    () =>
      Object.fromEntries(sections.map((section) => [section.id, section.title])),
    [],
  );

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
          Centralisez la gestion de votre activité, pilotez vos contenus, suivez vos opérations et accédez rapidement à vos outils d’administration.
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
