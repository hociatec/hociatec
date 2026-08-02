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
    matcher: ['diagnostic et dépannage informatique', 'depannage informatique'],
    illustration: {
      imageUrl: '/service-illustrations/depannage.svg',
      imageAlt: 'Illustration de diagnostic et dépannage informatique',
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
    matcher: ['formation bureautique et outils numériques', 'formation bureautique'],
    illustration: {
      imageUrl: '/service-illustrations/formation-bureautique.svg',
      imageAlt: 'Illustration de formation bureautique et outils numériques',
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
  const localMatch = SERVICE_ILLUSTRATIONS.find((entry) =>
    entry.matcher.some((matcher) => normalizedTitle.includes(normalizeTitle(matcher))),
  );

  if (localMatch) {
    return localMatch.illustration;
  }

  if (service.imageUrl?.trim()) {
    return {
      imageUrl: service.imageUrl.trim(),
      imageAlt: service.imageAlt?.trim() || service.title,
    };
  }

  return null;
};
