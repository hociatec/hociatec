import { Alert } from '@/shared/components/ui/Alert';
import {
  EmptyState,
  ErrorState,
  ForbiddenState,
  LoadingState,
  NotFoundState,
  PageState,
  PrimaryLink,
} from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const sectionClass = 'rounded-xl border border-brand-100 bg-white p-5 shadow-sm';

export const AdminUiCatalogPage = () => {
  useDocumentTitle('Admin - Catalogue UI');

  return (
    <div className="space-y-6">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold text-brand-900">Catalogue UI</h1>
        <p className="text-sm text-stone-600">
          Référence interne des composants partagés à utiliser avant de créer une variante locale.
        </p>
      </header>

      <section className={sectionClass}>
        <h2 className="mb-4 text-lg font-semibold text-brand-900">Alertes</h2>
        <div className="grid gap-3 md:grid-cols-2">
          <Alert>Information importante pour l’utilisateur.</Alert>
          <Alert variant="success">Action enregistrée avec succès.</Alert>
          <Alert variant="warning">Action possible mais à vérifier.</Alert>
          <Alert variant="error">Action impossible avec un rôle d’alerte.</Alert>
        </div>
      </section>

      <section className={sectionClass}>
        <h2 className="mb-4 text-lg font-semibold text-brand-900">États de page</h2>
        <div className="grid gap-3 md:grid-cols-2">
          <LoadingState>Chargement des données...</LoadingState>
          <EmptyState>Aucun élément à afficher.</EmptyState>
          <NotFoundState>Ressource introuvable.</NotFoundState>
          <ForbiddenState>Accès refusé.</ForbiddenState>
          <ErrorState onAction={() => undefined}>Erreur récupérable.</ErrorState>
          <PageState variant="success">État de succès générique.</PageState>
        </div>
      </section>

      <section className={sectionClass}>
        <h2 className="mb-4 text-lg font-semibold text-brand-900">Commandes</h2>
        <div className="flex flex-wrap items-center gap-3">
          <button className="button" type="button">
            Action principale
          </button>
          <button className="button button-muted" type="button">
            Action secondaire
          </button>
          <PrimaryLink to="/admin">Lien principal</PrimaryLink>
        </div>
      </section>

      <section className={sectionClass}>
        <h2 className="mb-4 text-lg font-semibold text-brand-900">Pagination</h2>
        <PaginationControls
          page={2}
          total={42}
          totalLabel="élément"
          totalPages={5}
          onPageChange={() => undefined}
        />
      </section>
    </div>
  );
};
