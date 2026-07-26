import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

const PRIVACY_UPDATED_AT = '20 juillet 2026';

export const PrivacyPage = () => {
  useDocumentTitle('Politique de confidentialité');
  useMetaTags({
    title: 'Politique de confidentialité — Hociatec',
    description:
      'Informations sur la collecte, l’usage, les bases légales, les durées de conservation et les droits liés aux données personnelles.',
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Politique de confidentialité</h1>
        <p className="text-sm text-gray-600 mb-6">Dernière mise à jour: {PRIVACY_UPDATED_AT}</p>

        <div className="prose prose-stone max-w-none">
          <h2>1. Responsable de traitement</h2>
          <p>
            Le responsable des traitements de données personnelles réalisés via le site
            <strong> hociatec.fr</strong> est <strong>Hociatec</strong>, SARL immatriculée au RCS de
            Nanterre sous le numéro SIREN 934 814 559, dont le siège social est situé au 2 allée
            Anatoli Vaisser, 92600 Asnières-sur-Seine, France.
          </p>
          <p>
            Contact pour toute demande relative aux données personnelles:
            <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a>.
          </p>

          <h2>2. Données collectées</h2>
          <p>Selon les fonctionnalités utilisées, Hociatec peut collecter notamment:</p>
          <ul>
            <li>
              données d’identification: nom, prénom, civilité, date de naissance si nécessaire;
            </li>
            <li>données de contact: email, téléphone, adresse postale;</li>
            <li>
              données de compte: identifiants, statut d’activation, rôles, historique de connexion;
            </li>
            <li>données de commande, panier, devis, facturation, paiement et livraison;</li>
            <li>données relatives aux rendez-vous, audits, demandes de support et messages;</li>
            <li>
              données techniques: adresse IP, journaux de sécurité, informations de navigation
              strictement nécessaires;
            </li>
            <li>
              données de consentement relatives aux cookies ou communications, le cas échéant.
            </li>
          </ul>

          <h2>3. Finalités et bases légales</h2>
          <p>Les données sont traitées pour les finalités suivantes:</p>
          <ul>
            <li>
              création et gestion du compte client: exécution du contrat ou mesures
              précontractuelles;
            </li>
            <li>
              gestion des commandes, devis, locations, paiements, livraisons et factures: exécution
              du contrat;
            </li>
            <li>
              réponse aux demandes de contact, support, rendez-vous et audits: intérêt légitime ou
              mesures précontractuelles;
            </li>
            <li>respect des obligations comptables, fiscales et légales: obligation légale;</li>
            <li>
              sécurisation du site, prévention de la fraude et gestion des incidents: intérêt
              légitime;
            </li>
            <li>
              amélioration du service et mesure d’audience non intrusive: intérêt légitime ou
              consentement selon les outils utilisés;
            </li>
            <li>
              communications commerciales: consentement lorsque requis ou intérêt légitime dans les
              limites autorisées.
            </li>
          </ul>

          <h2>4. Caractère obligatoire des données</h2>
          <p>
            Certaines données sont nécessaires pour créer un compte, traiter une commande, établir
            un devis, planifier un rendez-vous ou répondre à une demande. Lorsqu’une donnée est
            obligatoire, l’absence de réponse peut empêcher l’accès au service concerné.
          </p>

          <h2>5. Destinataires</h2>
          <p>
            Les données sont destinées aux personnes habilitées de Hociatec. Elles peuvent également
            être transmises, uniquement lorsque nécessaire, à des prestataires techniques,
            hébergeurs, prestataires de paiement, transporteurs, outils de messagerie, conseils,
            autorités administratives ou judiciaires.
          </p>
          <p>
            Les prestataires agissant pour le compte de Hociatec sont tenus à des obligations de
            confidentialité et de sécurité.
          </p>

          <h2>6. Paiement</h2>
          <p>
            Les paiements en ligne peuvent être traités par un prestataire spécialisé, notamment
            Stripe. Hociatec ne conserve pas les numéros complets de carte bancaire. Les
            informations de paiement sont traitées par le prestataire selon ses propres standards de
            sécurité.
          </p>

          <h2>7. Durées de conservation</h2>
          <p>Les données sont conservées pendant des durées proportionnées aux finalités:</p>
          <ul>
            <li>
              compte client: pendant la durée d’utilisation du compte, puis archivage si nécessaire;
            </li>
            <li>
              commandes, factures et pièces comptables: durée légale applicable, généralement
              jusqu’à 10 ans;
            </li>
            <li>
              devis et échanges précontractuels: durée nécessaire au suivi commercial, puis
              archivage limité;
            </li>
            <li>
              demandes de contact et support: durée nécessaire au traitement puis à la preuve de
              l’échange;
            </li>
            <li>
              journaux techniques et sécurité: durée limitée nécessaire à la sécurité et au
              diagnostic;
            </li>
            <li>
              cookies soumis à consentement: durée conforme aux règles applicables et aux choix de
              l’utilisateur.
            </li>
          </ul>

          <h2>8. Transferts hors Union européenne</h2>
          <p>
            Hociatec privilégie des prestataires situés dans l’Union européenne. Si certains
            prestataires impliquent un transfert de données hors Union européenne, Hociatec veille à
            ce que des garanties appropriées soient mises en place conformément au RGPD.
          </p>

          <h2>9. Sécurité</h2>
          <p>
            Hociatec met en œuvre des mesures techniques et organisationnelles destinées à protéger
            les données contre l’accès non autorisé, la perte, l’altération ou la divulgation.
            Aucune mesure de sécurité n’étant absolue, les utilisateurs sont invités à protéger
            leurs identifiants et à signaler toute anomalie.
          </p>

          <h2>10. Droits des personnes</h2>
          <p>
            Conformément à la réglementation applicable, les personnes concernées disposent de
            droits d’accès, de rectification, d’effacement, d’opposition, de limitation, de
            portabilité lorsque applicable, ainsi que du droit de retirer leur consentement à tout
            moment pour les traitements fondés sur celui-ci.
          </p>
          <p>
            Pour exercer ces droits, écrivez à
            <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a> en précisant votre demande
            et en joignant, si nécessaire, tout élément permettant de vérifier votre identité.
          </p>

          <h2>11. Réclamation auprès de la CNIL</h2>
          <p>
            Si vous estimez que vos droits ne sont pas respectés, vous pouvez introduire une
            réclamation auprès de la CNIL:{' '}
            <a href="https://www.cnil.fr" rel="noreferrer" target="_blank">
              www.cnil.fr
            </a>
            .
          </p>

          <h2>12. Cookies</h2>
          <p>
            Le site utilise des cookies techniques nécessaires au fonctionnement, à la sécurité, à
            la session utilisateur, au panier ou à l’authentification. Ces cookies ne nécessitent
            pas de consentement lorsqu’ils sont strictement nécessaires.
          </p>
          <p>
            Les cookies de mesure d’audience, publicitaires ou liés à des services tiers, lorsqu’ils
            ne sont pas strictement nécessaires, doivent faire l’objet d’un consentement préalable.
            L’utilisateur peut modifier ses choix via le mécanisme de gestion du consentement
            lorsqu’il est proposé sur le site.
          </p>

          <h2>13. Mise à jour</h2>
          <p>
            La présente politique peut être mise à jour pour tenir compte de l’évolution du site,
            des traitements ou de la réglementation. La date de mise à jour indique la version
            applicable.
          </p>
        </div>
      </div>
    </SiteLayout>
  );
};
