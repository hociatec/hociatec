import { Link, useLocation } from 'react-router';

import { useMarketingTemplatesList } from '../hooks/useMarketingTemplatesList';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const MarketingTemplatesListPage = () => {
  const location = useLocation();
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  useDocumentTitle(
    isTransactionalView ? 'Admin - E-mails transactionnels' : 'Admin - Modèles d’e-mail',
  );
  const {
    templates,
    segments,
    loading,
    query,
    setQuery,
    scenarioFilter,
    setScenarioFilter,
    usageFilter,
    setUsageFilter,
    statusFilter,
    setStatusFilter,
    error,
    message,
    filteredTemplates,
    scenarioOptions,
    handleDelete,
  } = useMarketingTemplatesList(isTransactionalView);

  return (
    <PageContainer
      size="admin"
      title={isTransactionalView ? 'E-mails transactionnels' : 'Modèles d’e-mail'}
      headerActions={
        <div className="flex flex-wrap gap-3">
          {isTransactionalView ? (
            <Link
              to="/admin"
              className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
            >
              Retour au dashboard
            </Link>
          ) : (
            <Link
              to="/admin/marketing"
              className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
            >
              Retour aux campagnes
            </Link>
          )}
          <PrimaryLink
            to={
              isTransactionalView
                ? '/admin/transactional-emails/new'
                : '/admin/marketing/templates/new'
            }
          >
            Nouveau modèle
          </PrimaryLink>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {templates.length} modèle{templates.length > 1 ? 's' : ''} enregistré
          {templates.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
          {isTransactionalView
            ? 'Gérez ici les e-mails automatiques envoyés par le site: comptes, commandes, devis, contact, bons et partage produit.'
            : 'Filtrez votre bibliothèque par usage métier, statut ou recherche libre pour retrouver rapidement le bon message.'}
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <FilterBar className="mb-6 rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <SearchFilter
          value={query}
          onChange={setQuery}
          placeholder="Nom, slug ou objet..."
          className="catalog-filter-bar__search"
        />
        {!isTransactionalView ? (
          <SelectFilter
            ariaLabel="Filtrer par usage"
            value={usageFilter}
            onChange={setUsageFilter}
            options={[
              { value: 'all', label: 'Tous' },
              { value: 'transactional', label: 'Transactionnels' },
              { value: 'campaign', label: 'Marketing' },
            ]}
          />
        ) : null}
        <SelectFilter
          ariaLabel="Filtrer par scénario"
          value={scenarioFilter}
          onChange={setScenarioFilter}
          options={scenarioOptions}
        />
        <SelectFilter
          ariaLabel="Filtrer par statut"
          value={statusFilter}
          onChange={setStatusFilter}
          options={[
            { value: 'all', label: 'Tous' },
            { value: 'active', label: 'Actifs' },
            { value: 'inactive', label: 'Désactivés' },
          ]}
        />
      </FilterBar>

      <AdminListState
        loading={loading}
        isEmpty={filteredTemplates.length === 0}
        loadingLabel="Chargement des modèles..."
        emptyLabel="Aucun modèle ne correspond aux filtres actuels."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Scénario</th>
                <th scope="col">Slug</th>
                <th scope="col">Statut</th>
                <th scope="col">Mise à jour</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredTemplates.map((template) => (
                <tr key={template.id}>
                  <th scope="row">
                    <strong>{template.name}</strong>
                    <div className="muted">{template.subjectTemplate}</div>
                  </th>
                  <td>
                    <div>{segments[template.scenarioKey]?.label ?? template.scenarioKey}</div>
                    <div className="muted">
                      {segments[template.scenarioKey]?.type === 'transactional'
                        ? 'Transactionnel'
                        : 'Marketing'}
                    </div>
                  </td>
                  <td>{template.slug}</td>
                  <td>{template.isActive ? 'Actif' : 'Désactivé'}</td>
                  <td>{formatOptionalFrenchDate(template.updatedAt)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={
                          isTransactionalView
                            ? `/admin/transactional-emails/${template.id}`
                            : `/admin/marketing/templates/${template.id}`
                        }
                        className="catalog-admin-actions__edit"
                        aria-label={`Voir le modèle ${template.name}`}
                      >
                        Voir
                      </Link>
                      <Link
                        to={
                          isTransactionalView
                            ? `/admin/transactional-emails/${template.id}/edit`
                            : `/admin/marketing/templates/${template.id}/edit`
                        }
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier le modèle ${template.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(template.id)}
                        aria-label={`Supprimer le modèle ${template.name}`}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
