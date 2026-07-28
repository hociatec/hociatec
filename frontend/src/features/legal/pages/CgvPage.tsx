import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

const CGV_UPDATED_AT = '20 juillet 2026';

export const CgvPage = () => {
  useDocumentTitle('Conditions générales de vente (CGV)');
  useMetaTags({
    title: 'CGV — Hociatec',
    description:
      'Conditions générales de vente applicables aux produits, locations, devis et prestations Hociatec.',
    type: 'article',
  });

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Informations légales"
        title="Conditions générales de vente (CGV)"
        description={`Dernière mise à jour: ${CGV_UPDATED_AT}`}
      >
        <PublicPageSection>
        <div className="prose prose-stone max-w-none">
          <h2>1. Champ d’application</h2>
          <p>
            Les présentes conditions générales de vente s’appliquent aux ventes de produits,
            locations de matériel, devis et prestations de services proposés par Hociatec via le
            site <strong>hociatec.fr</strong>, par devis ou par tout autre canal commercial.
          </p>
          <p>
            Toute commande implique l’acceptation sans réserve des présentes CGV par le client. Des
            conditions particulières peuvent compléter les présentes CGV lorsqu’elles figurent sur
            un devis, une commande, une facture ou un contrat spécifique.
          </p>

          <h2>2. Identification du vendeur</h2>
          <p>
            Hociatec, SARL au capital de 1 000 €, immatriculée au RCS de Nanterre sous le numéro
            SIREN 934 814 559, dont le siège social est situé au 2 allée Anatoli Vaisser, 92600
            Asnières-sur-Seine, France.
          </p>
          <p>
            Contact commercial et service client:{' '}
            <a href="mailto:contact@hociatec.fr">contact@hociatec.fr</a>.
          </p>

          <h2>3. Produits, services et locations</h2>
          <p>
            Hociatec propose notamment la vente et la location de matériel informatique,
            l’accompagnement technique, la conception et la maintenance de solutions numériques, les
            audits, formations et prestations associées.
          </p>
          <p>
            Les caractéristiques essentielles des produits ou services sont présentées sur les
            fiches produit, les pages de services, les devis ou tout document contractuel remis au
            client. Le client est invité à les lire attentivement avant toute commande.
          </p>

          <h2>4. Prix</h2>
          <p>
            Les prix sont indiqués en euros, toutes taxes comprises pour les clients consommateurs,
            sauf mention contraire. Pour les clients professionnels, certains documents commerciaux
            peuvent présenter des prix hors taxes, avec TVA indiquée séparément.
          </p>
          <p>
            Hociatec se réserve le droit de modifier ses prix à tout moment. Le prix applicable est
            celui affiché ou accepté au moment de la validation de la commande ou du devis.
          </p>

          <h2>5. Devis</h2>
          <p>
            Les devis émis par Hociatec sont valables pendant la durée indiquée sur le devis. À
            défaut de mention spécifique, la durée de validité est de 30 jours à compter de leur
            émission.
          </p>
          <p>
            L’acceptation d’un devis peut être matérialisée par signature, accord écrit, validation
            en ligne, paiement d’un acompte ou tout autre procédé convenu entre les parties.
          </p>

          <h2>6. Commande</h2>
          <p>
            Le client vérifie le contenu de sa commande avant validation. La commande devient ferme
            après confirmation et, le cas échéant, après paiement effectif. Hociatec peut refuser ou
            annuler toute commande anormale, frauduleuse, incomplète ou passée de mauvaise foi.
          </p>

          <h2>7. Paiement</h2>
          <p>
            Les moyens de paiement disponibles sont ceux proposés au moment de la commande. Les
            paiements en ligne peuvent être traités par des prestataires de paiement sécurisés,
            notamment Stripe.
          </p>
          <p>
            En cas de retard ou défaut de paiement, Hociatec peut suspendre la commande, la
            prestation, la livraison ou l’accès au service concerné jusqu’à régularisation.
          </p>

          <h2>8. Livraison et disponibilité</h2>
          <p>
            Les délais de livraison ou d’intervention sont communiqués à titre indicatif, sauf
            engagement écrit contraire. En cas d’indisponibilité d’un produit après commande,
            Hociatec informe le client et propose, selon le cas, un report, un produit équivalent,
            un avoir ou un remboursement.
          </p>
          <p>
            Le client doit vérifier l’état apparent des produits à réception et signaler toute
            réserve utile au transporteur et à Hociatec dans les meilleurs délais.
          </p>

          <h2>9. Location de matériel</h2>
          <p>
            Les conditions de location, notamment durée, prix, dépôt éventuel, modalités de remise,
            retour, retard, casse, perte ou restitution incomplète, sont précisées sur la fiche
            produit, dans le devis, la commande ou tout document contractuel remis au client.
          </p>
          <p>
            Le client s’engage à utiliser le matériel loué conformément à sa destination, avec soin,
            et à le restituer dans l’état convenu, hors usure normale.
          </p>

          <h2>10. Droit de rétractation des consommateurs</h2>
          <p>
            Lorsqu’il agit en qualité de consommateur et que le contrat est conclu à distance, le
            client dispose en principe d’un délai de 14 jours pour exercer son droit de
            rétractation, sauf exceptions légales.
          </p>
          <p>
            Le droit de rétractation peut notamment être exclu pour certains services pleinement
            exécutés avant la fin du délai avec accord préalable du client, pour les biens
            personnalisés ou pour les biens descellés ne pouvant être renvoyés pour des raisons
            d’hygiène ou de protection de la santé, selon les cas prévus par le Code de la
            consommation.
          </p>
          <p>
            Pour exercer son droit de rétractation, le client peut écrire à
            <a href="mailto:contact@hociatec.fr"> contact@hociatec.fr</a> en indiquant son identité,
            la commande concernée et sa volonté claire de se rétracter.
          </p>
          <p>
            Exemple de formulation: « Je vous notifie par la présente ma rétractation du contrat
            portant sur la commande [référence], passée le [date], au nom de [nom], à l’adresse
            [adresse]. »
          </p>

          <h2>11. Garanties légales</h2>
          <p>
            Les clients consommateurs bénéficient des garanties légales applicables, notamment la
            garantie légale de conformité et la garantie contre les vices cachés, dans les
            conditions prévues par les textes en vigueur.
          </p>
          <p>
            Pour toute demande de garantie ou de service après-vente, le client doit contacter
            Hociatec en décrivant le problème rencontré et en joignant tout justificatif utile.
          </p>

          <h2>12. Responsabilité</h2>
          <p>
            Hociatec est responsable de la bonne exécution de ses obligations contractuelles dans
            les limites prévues par la loi. Sa responsabilité ne saurait être engagée en cas de
            faute du client, d’usage non conforme, de force majeure, de fait imprévisible d’un tiers
            ou de mauvaise configuration d’un environnement non maîtrisé par Hociatec.
          </p>

          <h2>13. Clients professionnels</h2>
          <p>
            Pour les clients professionnels, des conditions particulières peuvent s’appliquer,
            notamment en matière de paiement, pénalités de retard, propriété intellectuelle,
            maintenance, disponibilité, recette, limitation de responsabilité ou confidentialité.
            Elles figurent le cas échéant dans le devis ou le contrat applicable.
          </p>

          <h2>14. Médiation de la consommation</h2>
          <p>
            Conformément aux règles applicables, le consommateur peut recourir gratuitement à un
            médiateur de la consommation en vue de la résolution amiable d’un litige, après avoir
            préalablement adressé une réclamation écrite à Hociatec.
          </p>
          <p>
            Les coordonnées du médiateur compétent doivent être complétées par Hociatec avant la
            mise en production si la société vend à des consommateurs.
          </p>

          <h2>15. Droit applicable et juridiction</h2>
          <p>
            Les présentes CGV sont soumises au droit français. En cas de litige, le client est
            invité à contacter Hociatec afin de rechercher une solution amiable. À défaut, les
            juridictions compétentes seront déterminées conformément aux règles légales applicables.
          </p>
        </div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
