export const SITE_URL = 'https://hociatec.fr';
export const CONTACT_EMAIL = 'contact@hociatec.fr';

export const DEFAULT_SEO = {
  siteName: 'Hociatec',
  title: 'Hociatec — Informatique, réparation et services numériques',
  description:
    "Vente de matériel informatique, réparation d'ordinateurs, maintenance informatique, création de site internet, assistance informatique et formation numérique.",
  locale: 'fr_FR',
  twitterCard: 'summary_large_image' as const,
  robots: 'index,follow',
  ogImagePath: '/og-default.png',
};

export const PRIVATE_ROBOTS_CONTENT = 'noindex,nofollow,noarchive';

export interface StaticRouteSeo {
  path: string;
  title: string;
  description: string;
  robots?: string;
}

export const toAbsoluteSiteUrl = (pathOrUrl: string) => {
  if (/^https?:\/\//u.test(pathOrUrl)) return pathOrUrl;

  return `${SITE_URL}${pathOrUrl.startsWith('/') ? pathOrUrl : `/${pathOrUrl}`}`;
};

export const PUBLIC_STATIC_ROUTE_SEO: StaticRouteSeo[] = [
  {
    path: '/',
    title: DEFAULT_SEO.title,
    description: DEFAULT_SEO.description,
  },
  {
    path: '/contact',
    title: 'Contact — Hociatec',
    description:
      'Contactez Hociatec pour une réparation, une assistance informatique, un devis ou une question sur le catalogue.',
  },
  {
    path: '/services',
    title: 'Services — Hociatec',
    description:
      'Découvrez le catalogue de services Hociatec avec les détails, la durée estimée et la base tarifaire de chaque offre.',
  },
  {
    path: '/formations',
    title: 'Formations — Hociatec',
    description:
      'Découvrez les formations Hociatec pour progresser sur les usages numériques, la bureautique et les outils informatiques.',
  },
  {
    path: '/catalogue/vente',
    title: 'Vente — Catalogue Hociatec',
    description:
      'Parcourez les produits informatiques Hociatec disponibles à la vente avec filtres, recherche et disponibilité.',
  },
  {
    path: '/catalogue/location',
    title: 'Location — Catalogue Hociatec',
    description:
      'Parcourez les produits informatiques Hociatec disponibles à la location avec filtres, recherche et disponibilité.',
  },
  {
    path: '/catalogue/recherche',
    title: 'Recherche catalogue — Hociatec',
    description: 'Recherchez rapidement un produit informatique dans le catalogue Hociatec.',
    robots: PRIVATE_ROBOTS_CONTENT,
  },
  {
    path: '/recherche',
    title: 'Recherche — Hociatec',
    description: 'Recherchez rapidement un produit, un service ou une formation Hociatec.',
    robots: PRIVATE_ROBOTS_CONTENT,
  },
  {
    path: '/legal/cgu',
    title: 'Conditions générales d’utilisation — Hociatec',
    description: 'Consultez les conditions générales d’utilisation du site Hociatec.',
  },
  {
    path: '/legal/cgv',
    title: 'Conditions générales de vente — Hociatec',
    description: 'Consultez les conditions générales de vente applicables aux offres Hociatec.',
  },
  {
    path: '/legal/confidentialite',
    title: 'Politique de confidentialité — Hociatec',
    description: 'Consultez la politique de confidentialité et de protection des données Hociatec.',
  },
  {
    path: '/legal/mentions-legales',
    title: 'Mentions légales — Hociatec',
    description: 'Consultez les mentions légales du site Hociatec.',
  },
];

export const resolveStaticRouteSeo = (path: string) =>
  PUBLIC_STATIC_ROUTE_SEO.find((entry) => entry.path === path);

const COMPANY_ADDRESS = {
  streetAddress: '2 allée Anatoli Vaisser',
  postalCode: '92600',
  addressLocality: 'Asnières-sur-Seine',
  addressCountry: 'FR',
};

export const ORGANIZATION_SCHEMA = {
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: DEFAULT_SEO.siteName,
  url: SITE_URL,
  email: CONTACT_EMAIL,
  logo: `${SITE_URL}${DEFAULT_SEO.ogImagePath}`,
  sameAs: [] as string[],
  address: {
    '@type': 'PostalAddress',
    ...COMPANY_ADDRESS,
  },
  contactPoint: [
    {
      '@type': 'ContactPoint',
      contactType: 'customer support',
      email: CONTACT_EMAIL,
      availableLanguage: ['French'],
    },
  ],
};

export const WEBSITE_SCHEMA = {
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: DEFAULT_SEO.siteName,
  url: SITE_URL,
  inLanguage: DEFAULT_SEO.locale,
  publisher: ORGANIZATION_SCHEMA,
};

export const LOCAL_BUSINESS_SCHEMA = {
  '@context': 'https://schema.org',
  '@type': 'LocalBusiness',
  name: DEFAULT_SEO.siteName,
  url: SITE_URL,
  email: CONTACT_EMAIL,
  image: `${SITE_URL}${DEFAULT_SEO.ogImagePath}`,
  address: {
    '@type': 'PostalAddress',
    ...COMPANY_ADDRESS,
  },
  areaServed: {
    '@type': 'AdministrativeArea',
    name: 'France',
  },
  sameAs: ORGANIZATION_SCHEMA.sameAs,
};
