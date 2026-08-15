import { useState } from 'react';
import { Link } from 'react-router';
import { useQuery } from '@tanstack/react-query';

import { fetchAdminRentals, type AdminRentalDto } from '../api';
import { adminRentalQueryKeys } from '../queryKeys';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { useSearchParams } from 'react-router';

const PER_PAGE = 20;

const customerLabel = (item: AdminRentalDto) => {
  const fullName = [item.customer.firstName, item.customer.lastName].filter(Boolean).join(' ').trim();
  if (fullName !== '') {
    return item.customer.email ? `${fullName} · ${item.customer.email}` : fullName;
  }

  return item.customer.email ?? 'Client non renseigné';
};

const requestLabel = (item: AdminRentalDto) => {
  if (item.request.status !== 'pending') {
    return 'Aucune';
  }

  if (item.request.type === 'extend') {
    return `Prolongation au ${formatDateInputForDisplay(item.request.requestedEndDate)}`;
  }

  if (item.request.type === 'end_early') {
    return `Fin anticipée au ${formatDateInputForDisplay(item.request.requestedEndDate)}`;
  }

  return 'Demande en attente';
};

export const AdminRentalsListPage = () => {
  useDocumentTitle('Admin - Locations');
  const [searchParams, setSearchParams] = useSearchParams();
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const page = parseNullablePositiveInteger(searchParams.get('page')) ?? 1;
  const timeline = searchParams.get('timeline') ?? 'all';
  const requestStatus = searchParams.get('requestStatus') ?? 'all';
  const requestType = searchParams.get('requestType') ?? 'all';

  const rentalsQuery = useQuery({
    queryKey: adminRentalQueryKeys.list(page, PER_PAGE, search, timeline, requestStatus, requestType),
    queryFn: () => fetchAdminRentals(page, PER_PAGE, search, timeline, requestStatus, requestType),
  });

  const updateParams = (next: Record<string, string | number>) => {
    const params = new URLSearchParams(searchParams);

    Object.entries(next).forEach(([key, value]) => {
      const normalized = String(value).trim();
      if (normalized === '' || normalized === 'all' || (key === 'page' && normalized === '1')) {
        params.delete(key);
      } else {
        params.set(key, normalized);
      }
    });

    setSearchParams(params);
  };

  const result = rentalsQuery.data;
  const items = result?.items ?? [];
  const meta = result?.meta ?? { page, perPage: PER_PAGE, total: 0, totalPages: 1 };
  const error = rentalsQuery.error ? getHttpErrorMessage(rentalsQuery.error, 'Impossible de charger les locations.') : null;

  return (
    <PageContainer size="admin" title="Locations">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {meta.total} location{meta.total > 1 ? 's' : ''} trouvée{meta.total > 1 ? 's' : ''}
          {meta.totalPages > 1 ? `, page ${meta.page} sur ${meta.totalPages}` : ''}.
        </p>
        <p className="text-sm text-stone-500">
          Suivez les locations à venir, en cours ou terminées, et traitez les demandes de prolongation ou de fin anticipée.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={(value) => {
            setSearch(value);
            updateParams({ q: value, page: 1 });
          }}
          placeholder="Produit, SKU, commande, client, e-mail..."
        />
        <SelectFilter
          value={timeline}
          onChange={(value) => updateParams({ timeline: value, page: 1 })}
          options={[
            { value: 'all', label: 'Toutes les périodes' },
            { value: 'upcoming', label: 'À venir' },
            { value: 'active', label: 'En cours' },
            { value: 'past', label: 'Terminées' },
          ]}
          ariaLabel="Filtre période"
        />
        <SelectFilter
          value={requestStatus}
          onChange={(value) => updateParams({ requestStatus: value, page: 1 })}
          options={[
            { value: 'all', label: 'Toutes les demandes' },
            { value: 'pending', label: 'Demandes en attente' },
            { value: 'none', label: 'Sans demande' },
          ]}
          ariaLabel="Filtre demande"
        />
        <SelectFilter
          value={requestType}
          onChange={(value) => updateParams({ requestType: value, page: 1 })}
          options={[
            { value: 'all', label: 'Tous les types' },
            { value: 'extend', label: 'Prolongations' },
            { value: 'end_early', label: 'Fins anticipées' },
          ]}
          ariaLabel="Filtre type de demande"
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={rentalsQuery.isLoading}
        isEmpty={!rentalsQuery.isLoading && items.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucune location ne correspond aux filtres actuels."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Produit</th>
                <th scope="col">Client</th>
                <th scope="col">Période</th>
                <th scope="col">Montant</th>
                <th scope="col">Statut</th>
                <th scope="col">Demande</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.orderItemId}>
                  <th scope="row">
                    <strong>{item.productName}</strong>
                    <div className="muted">{item.productSku}</div>
                    <div className="muted">Commande {item.orderNumber ?? '-'}</div>
                  </th>
                  <td>{customerLabel(item)}</td>
                  <td>
                    <div>Début: {formatDateInputForDisplay(item.startDate)}</div>
                    <div>Fin: {formatDateInputForDisplay(item.endDate)}</div>
                    <div className="muted">{item.rentalMonths ? `${item.rentalMonths} mois` : '-'}</div>
                  </td>
                  <td>{formatEuroCents(item.linePriceCents)}</td>
                  <td>{item.timelineStatusLabel}</td>
                  <td>{requestLabel(item)}</td>
                  <td>
                    <Link
                      className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                      to={`/admin/rentals/${item.orderItemId}`}
                      aria-label={`Ouvrir la location ${item.productName}`}
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
          page={meta.page}
          total={meta.total}
          totalLabel="location"
          totalPages={meta.totalPages}
          onPageChange={(updater) => updateParams({ page: updater(meta.page) })}
        />
      </AdminListState>
    </PageContainer>
  );
};
