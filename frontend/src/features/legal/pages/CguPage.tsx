import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const CguPage = () => {
  useDocumentTitle('Conditions générales d’utilisation (CGU)');
  useMetaTags({
    title: 'CGU — hociatec',
    description: "Conditions générales d’utilisation du site Hociatec.",
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Conditions générales d’utilisation (CGU)</h1>
        <p className="text-sm text-gray-600 mb-6">Dernière mise à jour: {new Date().toLocaleDateString('fr-FR')}</p>
        <div className="prose prose-slate max-w-none">
          <p>
            Les présentes conditions générales d’utilisation encadrent l’accès et l’usage du site et des services proposés par Hociatec.
            En accédant au site, vous acceptez sans réserve ces conditions.
          </p>
          <h2>Accès au service</h2>
          <p>
            Le site est accessible 7j/7 et 24h/24 sous réserve d’interruptions pour maintenance. Hociatec se réserve le droit de modifier ou suspendre l’accès au service.
          </p>
          <h2>Compte utilisateur</h2>
          <p>
            L’utilisateur est responsable de la confidentialité de ses identifiants et de l’usage de son compte. Toute activité réalisée via le compte est réputée effectuée par l’utilisateur.
          </p>
          <h2>Comportements interdits</h2>
          <p>
            Sont interdits notamment: atteinte à la sécurité du service, contournement des protections, utilisation illicite ou contraire aux droits de tiers.
          </p>
          <h2>Responsabilité</h2>
          <p>
            Hociatec ne saurait être tenue pour responsable des dommages indirects. Les informations sont fournies “en l’état”.
          </p>
          <h2>Contact</h2>
          <p>
            Pour toute question relative aux présentes CGU, contactez-nous via la page Contact.
          </p>
        </div>
      </div>
    </SiteLayout>
  );
};

