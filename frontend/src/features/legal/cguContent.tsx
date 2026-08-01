import { renderLegalSections, type LegalSectionContent } from './legalPageContent';

export const CGU_UPDATED_AT = '20 juillet 2026';

const cguSections: LegalSectionContent[] = [
  {
    title: '1. Objet',
    body: (
      <>
        <p>
          Les présentes CGU encadrent l’accès et l’utilisation du site{' '}
          <strong>hociatec.fr</strong>, du compte client, des formulaires, des espaces de suivi et
          des fonctionnalités proposées par Hociatec.
        </p>
        <p>
          En utilisant le site, l’utilisateur accepte les présentes CGU. Les ventes, locations et
          prestations sont en outre soumises aux conditions générales de vente applicables.
        </p>
      </>
    ),
  },
  {
    title: '2. Accès au site',
    body: (
      <>
        <p>
          Le site est en principe accessible 24h/24 et 7j/7, sauf maintenance, évolution,
          interruption technique, force majeure ou événement indépendant de la volonté de
          Hociatec.
        </p>
        <p>
          Hociatec peut faire évoluer, suspendre ou supprimer tout ou partie du site ou de ses
          fonctionnalités, notamment pour des raisons techniques, de sécurité ou de conformité.
        </p>
      </>
    ),
  },
  {
    title: '3. Compte utilisateur',
    body: (
      <>
        <p>
          Certaines fonctionnalités nécessitent la création d’un compte. L’utilisateur s’engage à
          fournir des informations exactes, à les maintenir à jour et à ne pas usurper l’identité
          d’un tiers.
        </p>
        <p>
          L’utilisateur est responsable de la confidentialité de ses identifiants. Toute action
          réalisée depuis son compte est présumée effectuée par lui, sauf preuve contraire.
        </p>
      </>
    ),
  },
  {
    title: '4. Sécurité',
    body: (
      <>
        <p>
          L’utilisateur s’engage à ne pas tenter d’accéder frauduleusement au site, aux serveurs,
          aux comptes d’autres utilisateurs, aux interfaces d’administration ou à tout système non
          autorisé.
        </p>
        <p>
          Toute faille, anomalie ou suspicion d’accès non autorisé peut être signalée à
          <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a>.
        </p>
      </>
    ),
  },
  {
    title: '5. Comportements interdits',
    body: (
      <>
        <p>Sont notamment interdits:</p>
        <ul>
          <li>l’utilisation du site à des fins frauduleuses, illicites ou contraires aux droits de tiers;</li>
          <li>la perturbation du fonctionnement normal du site ou de ses services;</li>
          <li>l’extraction massive ou automatisée de contenus sans autorisation;</li>
          <li>l’envoi de contenus malveillants, trompeurs, diffamatoires ou portant atteinte à autrui;</li>
          <li>le contournement de mesures de sécurité, d’authentification ou de limitation d’accès.</li>
        </ul>
      </>
    ),
  },
  {
    title: '6. Suspension ou suppression d’accès',
    body: (
      <p>
        Hociatec peut suspendre ou supprimer l’accès d’un utilisateur à tout ou partie du site en
        cas de violation des présentes CGU, suspicion de fraude, incident de sécurité, demande
        légale ou nécessité de protection du service.
      </p>
    ),
  },
  {
    title: '7. Contenus et informations',
    body: (
      <p>
        Hociatec s’efforce de présenter des informations exactes et actualisées. Toutefois, les
        informations publiées sur le site peuvent être modifiées à tout moment et ne constituent
        pas à elles seules un engagement contractuel, sauf validation expresse dans une commande,
        un devis ou un contrat.
      </p>
    ),
  },
  {
    title: '8. Propriété intellectuelle',
    body: (
      <p>
        Les contenus, marques, interfaces, textes, images, bases de données, logos, éléments
        graphiques et logiciels du site sont protégés. Toute reproduction ou exploitation non
        autorisée est interdite.
      </p>
    ),
  },
  {
    title: '9. Données personnelles',
    body: (
      <p>
        Les traitements de données personnelles réalisés via le site sont décrits dans la
        <a href="/confidentialite"> politique de confidentialité</a>.
      </p>
    ),
  },
  {
    title: '10. Responsabilité',
    body: (
      <p>
        Hociatec ne saurait être tenue responsable des dommages indirects, pertes de données,
        pertes d’exploitation ou préjudices résultant d’une utilisation non conforme du site, de
        l’environnement technique de l’utilisateur ou d’un événement extérieur.
      </p>
    ),
  },
  {
    title: '11. Modification des CGU',
    body: (
      <p>
        Hociatec peut modifier les présentes CGU pour tenir compte de l’évolution du site, de ses
        services ou de la réglementation. La version applicable est celle publiée sur le site au
        moment de l’utilisation.
      </p>
    ),
  },
  {
    title: '12. Contact',
    body: (
      <p>
        Pour toute question relative aux présentes CGU, vous pouvez écrire à
        <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a>.
      </p>
    ),
  },
];

export const cguContent = renderLegalSections(cguSections);
