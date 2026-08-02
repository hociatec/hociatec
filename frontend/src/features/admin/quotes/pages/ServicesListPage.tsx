import { Link } from 'react-router';

import { formatServiceDuration, useAdminServicesList } from '../hooks/useAdminServicesList';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents } from '@/shared/lib/formatters';

export const ServicesListPage = () => {
  useDocumentTitle('Admin - Services');
  const { loading, error, message, search, setSearch, filtered, handleDelete } =
    useAdminServicesList();

  return (
    <PageContainer
      size="admin"
      title="Services"
      headerActions={
        <Link to="/admin/services/new" className="register-form__submit">
          Nouveau service
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Gérez ici votre catalogue de services, leurs tarifs, leur mode de facturation et leur
          durée estimée.
        </p>
      </div>
      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher..." />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filtered.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucun service."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Titre</th>
                <th scope="col">Mode de facturation</th>
                <th scope="col">Accueil</th>
                <th scope="col">Durée</th>
                <th scope="col">Prix</th>
                <th scope="col">TVA</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((s) => (
                <tr key={s.id}>
                  <th scope="row">
                    <div className="catalog-admin-product-cell">
                      <strong>{s.title}</strong>
                      <span className="muted">{s.description ?? ''}</span>
                    </div>
                  </th>
                  <td>{s.unit?.trim() || 'Prix fixe'}</td>
                  <td>{s.isFeaturedHome ? 'Mis en avant' : '—'}</td>
                  <td>{formatServiceDuration(s)}</td>
                  <td>{formatEuroCents(s.priceCents)}</td>
                  <td>{s.vatRate?.toFixed(2) ?? '0'}%</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/services/${s.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier le service ${s.title}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(s.id)}
                        aria-label={`Supprimer le service ${s.title}`}
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
