import { Link } from 'react-router';

import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  useAdminAuditsList,
  isAuditSort,
  isAuditStatusFilter,
  isAuditTypeFilter,
} from '../hooks/useAdminAuditsList';
import { useAuditMetadata } from '@/features/audits/publicApi';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const AdminAuditsListPage = () => {
  useDocumentTitle('Admin - Audits');
  const {
    loading,
    error,
    search,
    setSearch,
    filterStatus,
    setFilterStatus,
    filterType,
    setFilterType,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    sort,
    setSort,
    view,
    paginatedView,
    page,
    setPage,
    totalPages,
  } = useAdminAuditsList();
  const { types, statuses } = useAuditMetadata();

  return (
    <PageContainer size="admin" title="Audits">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {view.length} audit{view.length > 1 ? 's' : ''} trouvé{view.length > 1 ? 's' : ''}
          {totalPages > 1 ? `, page ${page} sur ${totalPages}` : ''}.
        </p>
        <p className="text-sm text-stone-500">Filtrez par numéro, URL, type, statut et période.</p>
      </div>

      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher (numéro, URL)" />
        <SelectFilter
          value={filterType}
          onChange={(v) => {
            if (isAuditTypeFilter(v)) setFilterType(v);
          }}
          options={[
            { value: 'all', label: 'Tous les types' },
            ...types,
          ]}
          ariaLabel="Type"
        />
        <SelectFilter
          value={filterStatus}
          onChange={(v) => {
            if (isAuditStatusFilter(v)) setFilterStatus(v);
          }}
          options={[
            { value: 'all', label: 'Tous les statuts' },
            ...statuses,
          ]}
          ariaLabel="Statut"
        />
        <SelectFilter
          value={sort}
          onChange={(v) => {
            if (isAuditSort(v)) setSort(v);
          }}
          options={[
            { value: 'date_desc', label: 'Date : récent → ancien' },
            { value: 'date_asc', label: 'Date : ancien → récent' },
            { value: 'number_asc', label: 'Numéro : A → Z' },
            { value: 'number_desc', label: 'Numéro : Z → A' },
            { value: 'status_asc', label: 'Statut : progression' },
            { value: 'status_desc', label: 'Statut : régression' },
          ]}
          ariaLabel="Tri"
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

      <AdminListState
        loading={loading}
        isEmpty={!loading && view.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucun audit trouvé."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Numéro</th>
                <th scope="col">Type</th>
                <th scope="col">Statut</th>
                <th scope="col">URL</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {paginatedView.map((a) => (
                <tr key={a.id}>
                  <th scope="row">
                    <strong>{a.number}</strong>
                    <div className="muted">{formatOptionalFrenchDate(a.createdAt)}</div>
                  </th>
                    <td>{a.typeLabel}</td>
                    <td>{a.statusLabel}</td>
                  <td className="max-w-[320px] truncate">{a.url}</td>
                  <td>
                    <Link
                      className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                      to={`/admin/audits/${a.id}`}
                      aria-label={`Ouvrir l'audit ${a.number}`}
                    >
                      Ouvrir
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
        <PaginationControls
          page={page}
          total={view.length}
          totalLabel="audit"
          totalPages={totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
