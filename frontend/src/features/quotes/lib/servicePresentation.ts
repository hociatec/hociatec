import type { QuoteServiceDto } from '../types/quoteTypes';

type ServiceIllustration = {
  imageUrl: string;
  imageAlt: string;
};

const DEFAULT_FEATURED_SERVICE_TITLES = [
  'vente de matériel informatique',
  "réparation d'ordinateurs",
  'maintenance informatique',
  'création de sites web',
  'formation numérique',
  'informatique professionnelle',
] as const;

const SERVICE_ILLUSTRATIONS: Array<{
  matcher: string[];
  illustration: ServiceIllustration;
}> = [
  {
    matcher: ['création de site vitrine', 'site vitrine'],
    illustration: {
      imageUrl: '/service-illustrations/site-vitrine.svg',
      imageAlt: 'Illustration de création de site vitrine',
    },
  },
  {
    matcher: ['création de boutique e-commerce', 'boutique e-commerce', 'e-commerce'],
    illustration: {
      imageUrl: '/service-illustrations/ecommerce.svg',
      imageAlt: 'Illustration de création de boutique e-commerce',
    },
  },
  {
    matcher: ['maintenance de site web', 'maintenance site'],
    illustration: {
      imageUrl: '/service-illustrations/maintenance-site.svg',
      imageAlt: 'Illustration de maintenance de site web',
    },
  },
  {
    matcher: ['développement d’application web métier', 'application web métier', 'application web'],
    illustration: {
      imageUrl: '/service-illustrations/application-metier.svg',
      imageAlt: 'Illustration de développement d application web métier',
    },
  },
  {
    matcher: ['refonte de site internet', 'refonte site', 'refonte'],
    illustration: {
      imageUrl: '/service-illustrations/refonte-site.svg',
      imageAlt: 'Illustration de refonte de site internet',
    },
  },
  {
    matcher: [
      'audit d’accessibilité numérique',
      "audit d'accessibilité numérique",
      'accessibilité numérique',
    ],
    illustration: {
      imageUrl: '/service-illustrations/audit-accessibilite.svg',
      imageAlt: 'Illustration d audit d accessibilité numérique',
    },
  },
  {
    matcher: ['audit technique web', 'audit de performance web', 'audit seo technique', 'audit web'],
    illustration: {
      imageUrl: '/service-illustrations/audit-web.svg',
      imageAlt: 'Illustration d audit technique web',
    },
  },
  {
    matcher: ['audit ux et parcours utilisateur', 'audit ux', 'parcours utilisateur'],
    illustration: {
      imageUrl: '/service-illustrations/audit-ux.svg',
      imageAlt: 'Illustration d audit UX et parcours utilisateur',
    },
  },
  {
    matcher: ['diagnostic et dépannage informatique', 'depannage informatique'],
    illustration: {
      imageUrl: '/service-illustrations/depannage.svg',
      imageAlt: 'Illustration de diagnostic et dépannage informatique',
    },
  },
  {
    matcher: ['assistance informatique à distance', 'assistance informatique a distance', 'à distance'],
    illustration: {
      imageUrl: '/service-illustrations/assistance-distance.svg',
      imageAlt: 'Illustration d assistance informatique à distance',
    },
  },
  {
    matcher: ['installation et configuration de postes', 'configuration de postes'],
    illustration: {
      imageUrl: '/service-illustrations/installation-postes.svg',
      imageAlt: 'Illustration d installation et configuration de postes',
    },
  },
  {
    matcher: ['installation réseau et nas', 'installation reseau et nas', 'nas', 'réseau'],
    illustration: {
      imageUrl: '/service-illustrations/reseau-nas.svg',
      imageAlt: 'Illustration d installation réseau et NAS',
    },
  },
  {
    matcher: ['formation bureautique et outils numériques', 'formation bureautique'],
    illustration: {
      imageUrl: '/service-illustrations/formation-bureautique.svg',
      imageAlt: 'Illustration de formation bureautique et outils numériques',
    },
  },
  {
    matcher: ['formation cybersécurité et bonnes pratiques', 'cybersécurité', 'cybersecurite'],
    illustration: {
      imageUrl: '/service-illustrations/formation-cybersecurite.svg',
      imageAlt: 'Illustration de formation cybersécurité et bonnes pratiques',
    },
  },
  {
    matcher: ['reconditionnement et remise en service de matériel', 'reconditionnement', 'remise en service'],
    illustration: {
      imageUrl: '/service-illustrations/reconditionnement.svg',
      imageAlt: 'Illustration de reconditionnement et remise en service de matériel',
    },
  },
];

const normalizeTitle = (value: string | null | undefined) =>
  (value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const findDefaultFeaturedRank = (service: QuoteServiceDto) => {
  const normalizedTitle = normalizeTitle(service.title);
  return DEFAULT_FEATURED_SERVICE_TITLES.findIndex((title) =>
    normalizedTitle.includes(normalizeTitle(title)),
  );
};

export const selectFeaturedServices = (services: QuoteServiceDto[], limit = 6) => {
  const explicit = services.filter((service) => service.isFeaturedHome);

  if (explicit.length > 0) {
    return explicit.slice(0, limit);
  }

  return [...services]
    .map((service) => ({ service, rank: findDefaultFeaturedRank(service) }))
    .filter((entry) => entry.rank >= 0)
    .sort((left, right) => left.rank - right.rank)
    .slice(0, limit)
    .map((entry) => entry.service);
};

export const resolveServiceIllustration = (service: QuoteServiceDto): ServiceIllustration | null => {
  const normalizedTitle = normalizeTitle(service.title);

  if (service.imageUrl?.trim()) {
    return {
      imageUrl: service.imageUrl.trim(),
      imageAlt: service.imageAlt?.trim() || service.title,
    };
  }

  const localMatch = SERVICE_ILLUSTRATIONS.find((entry) =>
    entry.matcher.some((matcher) => normalizedTitle.includes(normalizeTitle(matcher))),
  );

  if (localMatch) {
    return localMatch.illustration;
  }

  return {
    imageUrl: '/service-illustrations/service-generique.svg',
    imageAlt: `Illustration du service ${service.title}`,
  };
};
