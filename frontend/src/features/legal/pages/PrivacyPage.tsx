import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const PrivacyPage = () => {
  useDocumentTitle('Politique de confidentialité');
  useMetaTags({
    title: 'Politique de confidentialité — hociatec',
    description: 'Informations sur la collecte, l’usage et la protection de vos données personnelles.',
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Politique de confidentialité</h1>
        <p className="text-sm text-gray-600 mb-6">Dernière mise à jour: {new Date().toLocaleDateString('fr-FR')}</p>
        <div className="prose prose-slate max-w-none">
          <h2>Responsable de traitement</h2>
          <p>Hociatec est responsable du traitement des données collectées via le site.</p>
          <h2>Données collectées</h2>
          <p>Nous collectons les données nécessaires à la gestion de vos demandes (compte, devis, commandes, rendez-vous, audits).</p>
          <h2>Finalités et bases légales</h2>
          <p>Fournir les services, exécuter le contrat, répondre aux obligations légales et améliorer nos services.</p>
          <h2>Durées de conservation</h2>
          <p>Les données sont conservées pour la durée nécessaire aux finalités, puis archivées ou supprimées.</p>
          <h2>Vos droits</h2>
          <p>Accès, rectification, suppression, opposition, limitation; contactez-nous via la page Contact pour exercer vos droits.</p>
          <h2>Cookies</h2>
          <p>Des cookies techniques peuvent être nécessaires au fonctionnement. Les cookies de mesure/marketing nécessitent votre consentement.</p>
        </div>
      </div>
    </SiteLayout>
  );
};

