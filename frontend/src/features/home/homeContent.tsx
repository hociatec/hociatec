import { Link } from 'react-router';
import {
  ArrowRight,
  Briefcase,
  CheckCircle2,
  Globe,
  GraduationCap,
  HeartHandshake,
  Monitor,
  ShieldCheck,
  Users,
  Wrench,
} from 'lucide-react';

export const heroServices = [
  { title: 'Vente de matériel informatique', Icon: Monitor },
  { title: "Réparation d'ordinateurs", Icon: Wrench },
  { title: 'Informatique professionnelle', Icon: Briefcase },
  { title: 'Création de sites web', Icon: Globe },
  { title: 'Maintenance informatique', Icon: ShieldCheck },
  { title: 'Formation numérique', Icon: GraduationCap },
];

export const audienceSections = [
  {
    title: 'Pour les particuliers',
    description:
      "Des solutions simples pour s'équiper, réparer un appareil et être accompagné au quotidien.",
    ctaLabel: 'Voir les services',
    ctaTo: '/services',
    items: [
      'Vente de matériel informatique',
      "Réparation d'ordinateurs",
      'Ordinateurs reconditionnés',
      'Formation numérique',
      'Assistance informatique',
    ],
  },
  {
    title: 'Pour les professionnels',
    description:
      "Un accompagnement structuré pour fiabiliser le parc, gagner du temps et faire évoluer vos outils.",
    ctaLabel: 'Demander un devis',
    ctaTo: '/devis/nouveau',
    items: [
      'Maintenance informatique',
      'Gestion de parc informatique',
      'Développement de sites web',
      'Solutions numériques',
      'Accompagnement informatique',
    ],
  },
];

export const trustItems = [
  'Produits garantis',
  'Accompagnement personnalisé',
  'Conseils avant achat',
  'Service de proximité',
  'Disponibilité',
  'Solutions adaptées aux besoins',
  'Suivi client',
  'Solutions durables',
];

export const HomeHeroSection = () => (
  <section className="home-hero animate-fade-in-up">
    <div className="home-hero__content">
      <p className="home-hero__eyebrow">Informatique, réparation et accompagnement numérique</p>
      <h1>Matériel informatique, réparation, maintenance et création web.</h1>
      <p>
        Hociatec accompagne les particuliers et les professionnels avec des solutions
        informatiques utiles, durables et compréhensibles dès le premier échange.
      </p>
      <ul className="home-hero__service-list" aria-label="Services principaux">
        {heroServices.map(({ title, Icon }) => (
          <li key={title}>
            <Icon aria-hidden="true" />
            <span>{title}</span>
          </li>
        ))}
      </ul>
      <div className="home-hero__actions">
        <Link to="/devis/nouveau" className="home-button home-button--primary">
          Demander un devis
        </Link>
        <Link to="/contact" className="home-button home-button--secondary">
          Nous contacter
        </Link>
        <Link to="/services" className="home-button home-button--ghost">
          Découvrir nos services
        </Link>
      </div>
    </div>
    <div className="home-hero__visual" aria-hidden="true">
      <img
        src="/hociatec-hero-workbench.webp"
        alt=""
        loading="eager"
        decoding="async"
        fetchPriority="high"
      />
      <div className="home-hero__metric">
        <strong>Des réponses concrètes pour chaque besoin.</strong>
        <span>
          Vente, réparation, assistance informatique, maintenance et création de site internet au
          même endroit.
        </span>
      </div>
    </div>
  </section>
);

export const HomeAudienceSection = () => (
  <section className="home-services animate-fade-in-up delay-100" aria-label="Parcours Hociatec">
    <div className="home-section-heading">
      <p>Des services pensés selon votre profil</p>
      <h2>Identifiez rapidement le bon accompagnement</h2>
    </div>
    <div className="home-services__grid home-services__grid--audiences">
      {audienceSections.map((section) => (
        <article key={section.title} className="home-service-card home-service-card--audience">
          <div className="home-service-card__body">
            <h3>{section.title}</h3>
            <p>{section.description}</p>
            <ul className="home-service-card__list">
              {section.items.map((item) => (
                <li key={item}>
                  <CheckCircle2 aria-hidden="true" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
          <Link to={section.ctaTo} className="home-service-card__link">
            {section.ctaLabel}
            <ArrowRight aria-hidden="true" />
          </Link>
        </article>
      ))}
    </div>
  </section>
);

export const HomeTrustSection = () => (
  <section className="home-trust animate-fade-in-up delay-200" aria-label="Éléments de réassurance">
    <div className="home-trust__panel">
      <div className="home-section-heading">
        <p>Des repères simples et transparents</p>
        <h2>Des engagements qui rassurent sans surpromesse</h2>
      </div>
      <div className="home-trust__grid">
        {trustItems.map((item) => (
          <div key={item} className="home-trust__item">
            <ShieldCheck aria-hidden="true" />
            <span>{item}</span>
          </div>
        ))}
      </div>
    </div>
  </section>
);

export const HomeStorySection = () => (
  <section id="histoire" className="home-story animate-fade-in-up delay-300">
    <div className="home-story__content">
      <div className="home-section-heading">
        <p>Notre histoire</p>
        <h2>Un accompagnement humain avant, pendant et après l’intervention</h2>
      </div>
      <p>
        Hociatec mise sur la clarté, l’écoute et des conseils adaptés à chaque situation. Le besoin
        est compris avant de proposer un matériel, une réparation, une maintenance ou une solution
        numérique.
      </p>
      <div className="home-story__points">
        <div className="home-story__point">
          <Users aria-hidden="true" />
          <div>
            <strong>Conseils adaptés</strong>
            <span>
              Chaque demande est traitée selon l’usage, le budget et les contraintes réelles.
            </span>
          </div>
        </div>
        <div className="home-story__point">
          <HeartHandshake aria-hidden="true" />
          <div>
            <strong>Suivi personnalisé</strong>
            <span>Un interlocuteur reste disponible pour répondre et ajuster si nécessaire.</span>
          </div>
        </div>
      </div>
    </div>
  </section>
);

export const HomeProductsHeading = () => (
  <div className="home-section-heading home-section-heading--row">
    <div>
      <p>Une sélection mise en avant par Hociatec</p>
      <h2>Produits recommandés</h2>
    </div>
    <Link to="/catalogue/vente" className="home-products__link">
      Voir le catalogue
      <ArrowRight aria-hidden="true" />
    </Link>
  </div>
);
