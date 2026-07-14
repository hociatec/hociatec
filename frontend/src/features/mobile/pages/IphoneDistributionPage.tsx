import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Headphones, House, Package, UserRound } from 'lucide-react';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { LOCAL_BUSINESS_SCHEMA, SITE_URL } from '@/shared/config/seoConfig';

import { IPHONE_DISTRIBUTION_PATH } from '../config/iphoneDistribution';

type MobileTab = 'home' | 'products' | 'account';

const sampleProducts = [
  {
    name: 'MacBook Pro 14 pouces',
    meta: 'Reconditionné • Vente',
    price: '1 249 €',
  },
  {
    name: 'Écran Dell 27"',
    meta: 'Bureautique • Vente',
    price: '219 €',
  },
  {
    name: 'Poste bureautique en location',
    meta: 'Professionnel • Location',
    price: '39 €/mois',
  },
];

export const IphoneDistributionPage = () => {
  const [activeTab, setActiveTab] = useState<MobileTab>('home');

  useDocumentTitle('App iPhone');
  useMetaTags({
    title: 'App iPhone — Hociatec',
    description:
      "Version web mobile minimaliste de l'expérience iPhone Hociatec, testable immédiatement sur iPhone.",
    type: 'website',
    canonicalUrl: `${SITE_URL}${IPHONE_DISTRIBUTION_PATH}`,
    structuredData: {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      name: 'App iPhone — Hociatec',
      url: `${SITE_URL}${IPHONE_DISTRIBUTION_PATH}`,
      description: "Prototype web mobile de l'application Hociatec.",
      publisher: LOCAL_BUSINESS_SCHEMA,
    },
  });

  const tabContent = useMemo(() => {
    if (activeTab === 'products') {
      return (
        <div className="space-y-3">
          <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Catalogue</p>
            <h2 className="mt-2 text-xl font-semibold text-slate-950">Produits Hociatec</h2>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Premier test mobile simple, pensé iPhone, avec des cartes lisibles et une navigation directe.
            </p>
          </div>
          {sampleProducts.map((product) => (
            <article key={product.name} className="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm">
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{product.meta}</p>
              <h3 className="mt-2 text-lg font-semibold text-slate-950">{product.name}</h3>
              <div className="mt-4 flex items-center justify-between">
                <span className="text-base font-bold text-sky-700">{product.price}</span>
                <button className="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white">
                  Voir
                </button>
              </div>
            </article>
          ))}
        </div>
      );
    }

    if (activeTab === 'account') {
      return (
        <div className="space-y-3">
          <div className="rounded-[1.5rem] bg-slate-950 p-5 text-white">
            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-sky-300">Compte</p>
            <h2 className="mt-2 text-2xl font-semibold">Espace client</h2>
            <p className="mt-3 text-sm leading-6 text-slate-200">
              Ici viendront ensuite la connexion, les devis, les commandes, les rendez-vous et les favoris.
            </p>
          </div>
          <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-sm font-medium text-slate-950">Actions rapides</p>
            <div className="mt-4 grid gap-3">
              <Link
                to="/login"
                className="rounded-[1rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800"
              >
                Se connecter
              </Link>
              <Link
                to="/contact"
                className="rounded-[1rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800"
              >
                Contacter Hociatec
              </Link>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="space-y-3">
        <div className="overflow-hidden rounded-[1.8rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-5 text-white shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-sky-300">Hociatec mobile</p>
          <h1 className="mt-3 text-3xl font-semibold leading-tight">Une base iPhone web, testable tout de suite</h1>
          <p className="mt-3 text-sm leading-6 text-slate-200">
            Ce prototype vous permet de juger le rendu, la lisibilité et la logique d’onglets directement sur iPhone,
            sans App Store, sans Expo Go et sans machine Apple.
          </p>
        </div>

        <div className="grid gap-3">
          <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-sm font-semibold text-slate-950">Ce test sert à quoi ?</p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Valider une sensation d’application mobile simple avant d’aller plus loin sur le natif.
            </p>
          </div>
          <div className="rounded-[1.4rem] border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-sm font-semibold text-slate-950">Ce qui viendra ensuite</p>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Recherche produits, compte client, panier et vraies fiches détaillées.
            </p>
          </div>
          <div className="rounded-[1.4rem] border border-slate-200 bg-sky-50 p-4 shadow-sm">
            <p className="text-sm font-semibold text-slate-950">Astuce iPhone</p>
            <p className="mt-2 text-sm leading-6 text-slate-700">
              Ouvrez cette page dans Safari puis ajoutez-la à l’écran d’accueil pour un rendu plus proche d’une app.
            </p>
          </div>
        </div>
      </div>
    );
  }, [activeTab]);

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 py-6 sm:px-6">
        <div className="mx-auto w-full max-w-[430px]">
          <div className="rounded-[2.2rem] border border-slate-200 bg-[linear-gradient(180deg,#f8fbff_0%,#eef4f8_100%)] p-3 shadow-[0_24px_80px_rgba(15,23,42,0.12)]">
            <div className="rounded-[1.8rem] bg-white p-3 shadow-inner">
              <div className="mx-auto mb-4 h-1.5 w-16 rounded-full bg-slate-200" />
              <div className="min-h-[620px]">{tabContent}</div>
              <nav
                aria-label="Navigation mobile Hociatec"
                className="mt-4 grid grid-cols-3 gap-2 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-2"
              >
                <TabButton
                  active={activeTab === 'home'}
                  icon={<House className="h-4 w-4" />}
                  label="Accueil"
                  onClick={() => setActiveTab('home')}
                />
                <TabButton
                  active={activeTab === 'products'}
                  icon={<Package className="h-4 w-4" />}
                  label="Produits"
                  onClick={() => setActiveTab('products')}
                />
                <TabButton
                  active={activeTab === 'account'}
                  icon={<UserRound className="h-4 w-4" />}
                  label="Compte"
                  onClick={() => setActiveTab('account')}
                />
              </nav>
            </div>
          </div>

          <div className="mt-4 rounded-[1.5rem] border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
            <div className="flex items-center gap-2 font-medium text-slate-900">
              <Headphones className="h-4 w-4 text-sky-700" />
              Prototype minimal
            </div>
            <p className="mt-2 leading-6">
              Cette première version vise seulement un test rapide du confort mobile sur iPhone. Si le rendu vous
              convient, je peux ensuite en faire une vraie web app beaucoup plus proche d’une application.
            </p>
          </div>
        </div>
      </main>
    </SiteLayout>
  );
};

const TabButton = ({
  active,
  icon,
  label,
  onClick,
}: {
  active: boolean;
  icon: ReactNode;
  label: string;
  onClick: () => void;
}) => (
  <button
    type="button"
    onClick={onClick}
    className={[
      'flex min-h-[72px] flex-col items-center justify-center rounded-[1.1rem] px-2 py-3 text-xs font-medium transition',
      active ? 'bg-slate-950 text-white shadow-sm' : 'bg-transparent text-slate-600',
    ].join(' ')}
  >
    <span className="mb-1">{icon}</span>
    <span>{label}</span>
  </button>
);
