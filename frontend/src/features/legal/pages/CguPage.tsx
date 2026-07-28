import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

const CGU_UPDATED_AT = '20 juillet 2026';

export const CguPage = () => {
  useDocumentTitle('Conditions générales d’utilisation (CGU)');
  useMetaTags({
    title: 'CGU — Hociatec',
    description:
      'Conditions générales d’utilisation du site, du compte client et des services numériques Hociatec.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Conditions générales d’utilisation (CGU)"
        description={`Dernière mise à jour: ${CGU_UPDATED_AT}`}
      >
        <PublicPageSection>
        <div className="prose prose-stone max-w-none">
          <h2>1. Objet</h2>
          <p>
            Les présentes CGU encadrent l’accès et l’utilisation du site{' '}
            <strong>hociatec.fr</strong>, du compte client, des formulaires, des espaces de suivi et
            des fonctionnalités proposées par Hociatec.
          </p>
          <p>
            En utilisant le site, l’utilisateur accepte les présentes CGU. Les ventes, locations et
            prestations sont en outre soumises aux conditions générales de vente applicables.
          </p>

          <h2>2. Accès au site</h2>
          <p>
            Le site est en principe accessible 24h/24 et 7j/7, sauf maintenance, évolution,
            interruption technique, force majeure ou événement indépendant de la volonté de
            Hociatec.
          </p>
          <p>
            Hociatec peut faire évoluer, suspendre ou supprimer tout ou partie du site ou de ses
            fonctionnalités, notamment pour des raisons techniques, de sécurité ou de conformité.
          </p>

          <h2>3. Compte utilisateur</h2>
          <p>
            Certaines fonctionnalités nécessitent la création d’un compte. L’utilisateur s’engage à
            fournir des informations exactes, à les maintenir à jour et à ne pas usurper l’identité
            d’un tiers.
          </p>
          <p>
            L’utilisateur est responsable de la confidentialité de ses identifiants. Toute action
            réalisée depuis son compte est présumée effectuée par lui, sauf preuve contraire.
          </p>

          <h2>4. Sécurité</h2>
          <p>
            L’utilisateur s’engage à ne pas tenter d’accéder frauduleusement au site, aux serveurs,
            aux comptes d’autres utilisateurs, aux interfaces d’administration ou à tout système non
            autorisé.
          </p>
          <p>
            Toute faille, anomalie ou suspicion d’accès non autorisé peut être signalée à
            <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a>.
          </p>

          <h2>5. Comportements interdits</h2>
          <p>Sont notamment interdits:</p>
          <ul>
            <li>
              l’utilisation du site à des fins frauduleuses, illicites ou contraires aux droits de
              tiers;
            </li>
            <li>la perturbation du fonctionnement normal du site ou de ses services;</li>
            <li>l’extraction massive ou automatisée de contenus sans autorisation;</li>
            <li>
              l’envoi de contenus malveillants, trompeurs, diffamatoires ou portant atteinte à
              autrui;
            </li>
            <li>
              le contournement de mesures de sécurité, d’authentification ou de limitation d’accès.
            </li>
          </ul>

          <h2>6. Suspension ou suppression d’accès</h2>
          <p>
            Hociatec peut suspendre ou supprimer l’accès d’un utilisateur à tout ou partie du site
            en cas de violation des présentes CGU, suspicion de fraude, incident de sécurité,
            demande légale ou nécessité de protection du service.
          </p>

          <h2>7. Contenus et informations</h2>
          <p>
            Hociatec s’efforce de présenter des informations exactes et actualisées. Toutefois, les
            informations publiées sur le site peuvent être modifiées à tout moment et ne constituent
            pas à elles seules un engagement contractuel, sauf validation expresse dans une
            commande, un devis ou un contrat.
          </p>

          <h2>8. Propriété intellectuelle</h2>
          <p>
            Les contenus, marques, interfaces, textes, images, bases de données, logos, éléments
            graphiques et logiciels du site sont protégés. Toute reproduction ou exploitation non
            autorisée est interdite.
          </p>

          <h2>9. Données personnelles</h2>
          <p>
            Les traitements de données personnelles réalisés via le site sont décrits dans la
            <a href="/confidentialite"> politique de confidentialité</a>.
          </p>

          <h2>10. Responsabilité</h2>
          <p>
            Hociatec ne saurait être tenue responsable des dommages indirects, pertes de données,
            pertes d’exploitation ou préjudices résultant d’une utilisation non conforme du site, de
            l’environnement technique de l’utilisateur ou d’un événement extérieur.
          </p>

          <h2>11. Modification des CGU</h2>
          <p>
            Hociatec peut modifier les présentes CGU pour tenir compte de l’évolution du site, de
            ses services ou de la réglementation. La version applicable est celle publiée sur le
            site au moment de l’utilisation.
          </p>

          <h2>12. Contact</h2>
          <p>
            Pour toute question relative aux présentes CGU, vous pouvez écrire à
            <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a>.
          </p>
        </div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
