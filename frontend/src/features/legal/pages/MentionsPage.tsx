import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const MentionsPage = () => {
  useDocumentTitle('Mentions légales');
  useMetaTags({
    title: 'Mentions légales — hociatec',
    description: 'Informations légales: éditeur du site, hébergeur, contacts et droits.',
    type: 'article',
  });

  return (
    <SiteLayout>
      <div className="container mx-auto max-w-3xl px-4 py-10">
        <h1 className="text-3xl font-semibold mb-6">Mentions légales</h1>
        <div className="prose prose-slate max-w-none">
          <h2>Éditeur</h2>
          <p>Hociatec — Informations de la société (RCS/SIRET, adresse, email, téléphone).</p>
          <h2>Hébergeur</h2>
          <p>Raison sociale, adresse, téléphone de l’hébergeur du site.</p>
          <h2>Propriété intellectuelle</h2>
          <p>Le contenu du site est protégé. Toute reproduction non autorisée est interdite.</p>
          <h2>Crédits</h2>
          <p>Crédits photos/illustrations, bibliothèques et technologies utilisées.</p>
        </div>
      </div>
    </SiteLayout>
  );
};

