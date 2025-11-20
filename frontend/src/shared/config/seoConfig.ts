export const SITE_URL = 'https://hociatec.fr';
export const CONTACT_EMAIL = 'contact@hociatec.fr';

export const DEFAULT_SEO = {
  siteName: 'Hociatec',
  title: 'Hociatec — Le numérique à taille humaine',
  description:
    'Vente, location, reprise, formation et audits numériques. Hociatec accompagne particuliers et professionnels avec des solutions durables et accessibles.',
  locale: 'fr_FR',
  twitterCard: 'summary_large_image' as const,
  robots: 'index,follow',
  ogImagePath: '/og-default.png',
};

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
