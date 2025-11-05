import { Link } from 'react-router-dom';
import { ArrowRight, CalendarDays, Layers3, Package, Plus, FileText, ShieldCheck } from 'lucide-react';

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

const sections: Section[] = [
  {
    id: 'prestations',
    title: 'Prestations et creneaux',
    subtitle: 'Gerez vos services et vos horaires.',
    links: [
      {
        to: '/admin/appointments/prestations',
        title: 'Lister les prestations',
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/appointments/prestations/new',
        title: 'Ajouter une prestation',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/appointments/schedule',
        title: 'Configurer les creneaux',
        icon: <CalendarDays className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'rendezvous',
    title: 'Rendez-vous',
    subtitle: 'Consultez et planifiez vos rendez-vous.',
    links: [
      {
        to: '/appointments/me',
        title: 'Lister les rendez-vous',
        icon: <CalendarDays className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/appointments/book',
        title: 'Ajouter un rendez-vous',
        icon: <Plus className="h-6 w-6 text-brand-400" />,
      },
    ],
  },
  {
    id: 'catalogue',
    title: 'Catalogue produits',
    subtitle: 'Gerez vos categories et vos produits.',
    links: [
      {
        to: '/admin/catalog/categories',
        title: 'Lister les categories',
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/catalog/categories/new',
        title: 'Ajouter une categorie',
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
    id: 'devis',
    title: 'Devis',
    subtitle: 'Gerez vos devis et votre catalogue de services.',
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
      {
        to: '/admin/quotes/services',
        title: 'Services (catalogue)',
        icon: <Layers3 className="h-6 w-6 text-brand-400" />,
      },
      {
        to: '/admin/quotes/services/new',
        title: 'Nouveau service',
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
  , {
    id: 'commandes',
    title: 'Commandes',
    subtitle: 'Gerez les commandes clients et leurs statuts.',
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
          Accedez rapidement a vos outils de gestion.
        </p>
      </header>

      <Tabs defaultValue={sections[0]?.id ?? ''} className="w-full">
        <TabsList className="mx-auto mb-10 grid w-full max-w-4xl grid-cols-1 gap-3 sm:grid-cols-4">
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
