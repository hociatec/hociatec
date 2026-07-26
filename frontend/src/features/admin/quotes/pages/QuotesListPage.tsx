import { Link } from 'react-router-dom';

import { useAdminQuotesList } from '../hooks/useAdminQuotesList';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';

export const QuotesListPage = () => {
  useDocumentTitle('Admin - Devis');
  const {
    loading,
    error,
    message,
    search,
    setSearch,
    filterStatus,
    setFilterStatus,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    filtered,
    handleDelete,
    handleDuplicate,
    handleSendEmail,
  } = useAdminQuotesList();

  return (
    <PageContainer
      size="admin"
      title="Devis"
      headerActions={<PrimaryLink to="/admin/quotes/new">Nouveau devis</PrimaryLink>}
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filtered.length} devis affiché{filtered.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">Filtrez par numéro, client, statut et période.</p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par numéro, client..."
        />
        <SelectFilter
          value={filterStatus}
          onChange={setFilterStatus}
          options={[
            { value: 'all', label: 'Tous les statuts' },
            { value: 'draft', label: 'Brouillon' },
            { value: 'sent', label: 'Envoyé' },
            { value: 'accepted', label: 'Accepté' },
            { value: 'refused', label: 'Refusé' },
            { value: 'expired', label: 'Expiré' },
          ]}
          ariaLabel="Statut"
        />
        <DateRangeFilter
          from={fromDate}
          to={toDate}
          onChange={({ from, to }) => {
            setFromDate(from);
            setToDate(to);
          }}
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filtered.length === 0}
        loadingLabel="Chargement des devis..."
        emptyLabel="Aucun devis."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Numéro</th>
                <th scope="col">Client</th>
                <th scope="col">E-mail</th>
                <th scope="col">Statut</th>
                <th scope="col">Fin de validité</th>
                <th scope="col">Total TTC</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((q) => (
                <tr key={q.id}>
                  <th scope="row">
                    <Link
                      to={`/admin/quotes/${q.id}`}
                      className="catalog-admin-table__primary-link"
                    >
                      <strong>{q.number}</strong>
                    </Link>
                  </th>
                  <td>
                    <strong>{q.customer?.name ?? '-'}</strong>
                  </td>
                  <td>{q.customer?.email ?? '-'}</td>
                  <td>{q.statusLabel}</td>
                  <td>{formatDateInputForDisplay(q.validUntil)}</td>
                  <td>{formatEuroCents(q?.totals?.ttc ?? 0)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/quotes/${q.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label="Modifier"
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => void handleSendEmail(q.id)}
                        aria-label="Envoyer"
                      >
                        Envoyer
                      </button>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => void handleDuplicate(q.id)}
                        aria-label="Dupliquer"
                      >
                        Dupliquer
                      </button>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(q.id)}
                        aria-label="Supprimer"
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
