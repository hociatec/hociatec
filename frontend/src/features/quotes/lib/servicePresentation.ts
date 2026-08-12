import type { QuoteServiceDto } from '../types/quoteTypes';
import { normalizeSearchText } from '@/shared/lib/searchText';
import { resolvePublicAssetUrl } from '@/shared/lib/publicAssetUrl';

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

const buildLocalIllustration = (fileName: string, imageAlt: string): ServiceIllustration => ({
  imageUrl: resolvePublicAssetUrl(`/service-illustrations/${fileName}`),
  imageAlt,
});

const SERVICE_ILLUSTRATIONS: Array<{
  matcher: string[];
  illustration: ServiceIllustration;
}> = [
  {
    matcher: ['création de site vitrine', 'site vitrine'],
    illustration: buildLocalIllustration('site-vitrine.svg', 'Illustration de création de site vitrine'),
  },
  {
    matcher: ['création de boutique e-commerce', 'boutique e-commerce', 'e-commerce'],
    illustration: buildLocalIllustration('ecommerce.svg', 'Illustration de création de boutique e-commerce'),
  },
  {
    matcher: ['maintenance de site web', 'maintenance site'],
    illustration: buildLocalIllustration('maintenance-site.svg', 'Illustration de maintenance de site web'),
  },
  {
    matcher: ['développement d’application web métier', 'application web métier', 'application web'],
    illustration: buildLocalIllustration('application-metier.svg', 'Illustration de développement d application web métier'),
  },
  {
    matcher: ['refonte de site internet', 'refonte site', 'refonte'],
    illustration: buildLocalIllustration('refonte-site.svg', 'Illustration de refonte de site internet'),
  },
  {
    matcher: [
      'audit d’accessibilité numérique',
      "audit d'accessibilité numérique",
      'accessibilité numérique',
    ],
    illustration: buildLocalIllustration('audit-accessibilite.svg', 'Illustration d audit d accessibilité numérique'),
  },
  {
    matcher: ['audit technique web', 'audit de performance web', 'audit seo technique', 'audit web'],
    illustration: buildLocalIllustration('audit-web.svg', 'Illustration d audit technique web'),
  },
  {
    matcher: ['audit ux et parcours utilisateur', 'audit ux', 'parcours utilisateur'],
    illustration: buildLocalIllustration('audit-ux.svg', 'Illustration d audit UX et parcours utilisateur'),
  },
  {
    matcher: ['diagnostic et dépannage informatique', 'depannage informatique'],
    illustration: buildLocalIllustration('depannage.svg', 'Illustration de diagnostic et dépannage informatique'),
  },
  {
    matcher: ['assistance informatique à distance', 'assistance informatique a distance', 'à distance'],
    illustration: buildLocalIllustration('assistance-distance.svg', 'Illustration d assistance informatique à distance'),
  },
  {
    matcher: ['installation et configuration de postes', 'configuration de postes'],
    illustration: buildLocalIllustration('installation-postes.svg', 'Illustration d installation et configuration de postes'),
  },
  {
    matcher: ['installation réseau et nas', 'installation reseau et nas', 'nas', 'réseau'],
    illustration: buildLocalIllustration('reseau-nas.svg', 'Illustration d installation réseau et NAS'),
  },
  {
    matcher: ['formation bureautique et outils numériques', 'formation bureautique'],
    illustration: buildLocalIllustration('formation-bureautique.svg', 'Illustration de formation bureautique et outils numériques'),
  },
  {
    matcher: ['formation cybersécurité et bonnes pratiques', 'cybersécurité', 'cybersecurite'],
    illustration: buildLocalIllustration('formation-cybersecurite.svg', 'Illustration de formation cybersécurité et bonnes pratiques'),
  },
  {
    matcher: ['reconditionnement et remise en service de matériel', 'reconditionnement', 'remise en service'],
    illustration: buildLocalIllustration('reconditionnement.svg', 'Illustration de reconditionnement et remise en service de matériel'),
  },
];

const normalizeTitle = (value: string | null | undefined) => normalizeSearchText(value).trim();

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
    const imageUrl = service.imageUrl.trim();

    return {
      imageUrl: imageUrl.startsWith('/service-illustrations/')
        ? resolvePublicAssetUrl(imageUrl)
        : imageUrl,
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
    imageUrl: resolvePublicAssetUrl('/service-illustrations/service-generique.svg'),
    imageAlt: `Illustration du service ${service.title}`,
  };
};
