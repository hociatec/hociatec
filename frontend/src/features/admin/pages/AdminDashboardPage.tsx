import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { fetchAdminDashboard, type AdminDashboardDto } from '@/features/admin/customers/api';
import { AdminDashboardHome } from '@/features/admin/extracted/dashboard/AdminDashboardHome';
import { adminDashboardSections as sections } from '@/features/admin/extracted/dashboard/adminDashboardSections';
import { readDefaultAdminTab, writeDefaultAdminTab } from '@/features/admin/utils/adminDashboardStorage';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

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
        setDashboardError(error instanceof Error ? error.message : "Les indicateurs d'administration n'ont pas pu être chargés.");
      });
  }, []);

  const sectionTitleMap = useMemo(
    () => Object.fromEntries(sections.map((section) => [section.id, section.title])),
    [],
  );

  const saveDefaultSection = (sectionId: string) => {
    setDefaultSection(sectionId);
    writeDefaultAdminTab(sectionId);
    setSavedMessage(`Vue d’accueil admin définie sur « ${sectionTitleMap[sectionId] ?? sectionId} ».`);
  };

  return (
    <section className="mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 py-12">
      <header className="mx-auto max-w-3xl text-center">
        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-stone-400">
          Espace administration
        </p>
        <h1 className="mt-3 text-4xl font-bold text-white sm:text-5xl">Tableau de bord</h1>
        <p className="mt-5 text-base text-stone-500">
          Suivez les priorités du jour, contrôlez les opérations sensibles et accédez rapidement aux espaces de gestion.
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
              className="rounded-xl bg-brand-800/60 py-3 text-sm font-medium text-stone-500 transition hover:bg-brand-700/70 data-[state=active]:bg-brand-600 data-[state=active]:text-white"
            >
              {section.title}
            </TabsTrigger>
          ))}
        </TabsList>

        {sections.map((section) => (
          <TabsContent key={section.id} value={section.id} className="flex flex-col gap-10">
            <div className="text-center">
              <h2 className="text-2xl font-semibold text-white">{section.title}</h2>
              <p className="mt-2 text-stone-400">{section.subtitle}</p>
            </div>

            {section.id === 'home' ? (
              <AdminDashboardHome
                dashboard={dashboard}
                dashboardError={dashboardError}
                dashboardStatus={dashboardStatus}
                defaultSection={defaultSection}
                savedMessage={savedMessage}
                sectionTitleMap={sectionTitleMap}
                sections={sections}
                onDefaultSectionChange={saveDefaultSection}
              />
            ) : null}

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {section.links.map((link) => (
                <Link
                  key={link.to}
                  to={link.to}
                  className="group relative overflow-hidden rounded-2xl border border-brand-700 bg-brand-800/50 p-6 transition-all hover:-translate-y-1 hover:border-brand-500 hover:bg-brand-800/80"
                >
                  <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-700/50 group-hover:bg-brand-600/20">
                      {link.icon}
                    </div>
                    <span className="text-base font-semibold text-white group-hover:text-brand-300">
                      {link.title}
                    </span>
                  </div>
                  <ArrowRight className="absolute right-4 top-4 h-4 w-4 text-stone-500 opacity-0 transition-all group-hover:right-3 group-hover:opacity-100 group-hover:text-brand-400" />
                </Link>
              ))}
            </div>
          </TabsContent>
        ))}
      </Tabs>
    </section>
  );
};
