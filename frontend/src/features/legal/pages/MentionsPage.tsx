import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

const LEGAL_UPDATED_AT = '20 juillet 2026';

export const MentionsPage = () => {
  useDocumentTitle('Mentions légales');
  useMetaTags({
    title: 'Mentions légales — Hociatec',
    description:
      'Identification de l’éditeur du site Hociatec, hébergement, propriété intellectuelle, données personnelles et contact.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Mentions légales"
        description={`Dernière mise à jour: ${LEGAL_UPDATED_AT}`}
      >
        <PublicPageSection>
        <div className="prose prose-stone max-w-none">
          <h2>Éditeur du site</h2>
          <p>
            Le site <strong>hociatec.fr</strong> est édité par <strong>Hociatec</strong>, société à
            responsabilité limitée (SARL) au capital social de <strong>1 000 €</strong>.
          </p>
          <p>
            Siège social: <strong>2 allée Anatoli Vaisser, 92600 Asnières-sur-Seine, France</strong>
            .
          </p>
          <p>
            SIREN: <strong>934 814 559</strong>
            <br />
            SIRET du siège: <strong>934 814 559 00019</strong>
            <br />
            Numéro de TVA intracommunautaire: <strong>FR59 934 814 559</strong>
            <br />
            RCS: <strong>Nanterre</strong>
            <br />
            Code APE: <strong>4791B - Vente à distance sur catalogue spécialisé</strong>
          </p>
          <p>
            Activité: vente et location de matériel informatique, conception, développement et
            maintenance de solutions numériques, conseil, assistance, audits et prestations
            associées.
          </p>
          <p>
            Contact: <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>
          </p>

          <h2>Direction de la publication</h2>
          <p>
            La direction de la publication est assurée par les représentants légaux de Hociatec:
            <strong> Hacene Sahraoui</strong> et <strong>Hocine Sahraoui</strong>, cogérants.
          </p>

          <h2>Hébergement</h2>
          <p>
            Le site est hébergé par <strong>OVH SAS</strong> (OVHcloud), société par actions
            simplifiée dont le siège social est situé au{' '}
            <strong>2 rue Kellermann, 59100 Roubaix, France</strong>.
          </p>
          <p>
            Site web de l’hébergeur:{' '}
            <a href="https://www.ovhcloud.com/fr/" rel="noreferrer" target="_blank">
              ovhcloud.com
            </a>
            .
          </p>

          <h2>Propriété intellectuelle</h2>
          <p>
            L’ensemble des contenus présents sur le site, notamment les textes, interfaces,
            graphismes, logos, photographies, vidéos, icônes, bases de données, éléments de marque
            et codes sources, est protégé par le droit de la propriété intellectuelle.
          </p>
          <p>
            Toute reproduction, représentation, adaptation, extraction, diffusion ou exploitation,
            totale ou partielle, sans autorisation écrite préalable de Hociatec est interdite, sauf
            exceptions prévues par la loi.
          </p>

          <h2>Données personnelles</h2>
          <p>
            Les traitements de données personnelles réalisés via le site sont décrits dans la
            <a href="/confidentialite"> politique de confidentialité</a>. Les utilisateurs peuvent
            exercer leurs droits en écrivant à{' '}
            <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>.
          </p>

          <h2>Cookies et traceurs</h2>
          <p>
            Le site peut utiliser des cookies techniques strictement nécessaires à son
            fonctionnement. Les cookies de mesure d’audience, publicitaires ou assimilés, lorsqu’ils
            ne sont pas strictement nécessaires, ne sont déposés qu’avec le consentement préalable
            de l’utilisateur.
          </p>

          <h2>Responsabilité</h2>
          <p>
            Hociatec s’efforce de fournir des informations exactes et à jour. Toutefois, des
            erreurs, omissions ou indisponibilités temporaires peuvent survenir. L’utilisateur reste
            responsable de l’usage qu’il fait des informations et services proposés sur le site.
          </p>
          <p>
            Les liens externes éventuellement présents sur le site sont fournis à titre informatif.
            Hociatec ne contrôle pas les contenus publiés par des tiers et ne saurait être tenue
            responsable de ces contenus.
          </p>

          <h2>Signalement et contact</h2>
          <p>
            Pour toute question relative au site, aux présentes mentions légales ou pour signaler un
            contenu, vous pouvez écrire à{' '}
            <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>.
          </p>
        </div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
