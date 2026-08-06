import { Link } from 'react-router';

import { PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { NotFoundState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const NotFoundPage = () => {
  useDocumentTitle('Page introuvable');

  return (
    <PublicPageShell
      description="Cette adresse ne correspond à aucune page disponible."
      title="Page introuvable"
    >
      <NotFoundState>
        <div className="mx-auto max-w-xl space-y-4">
          <p>Vérifiez l’adresse ou revenez à l’accueil.</p>
          <Link className="button" to="/">
            Retour à l’accueil
          </Link>
        </div>
      </NotFoundState>
    </PublicPageShell>
  );
};
