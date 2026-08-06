import { useCallback, useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';

import { fetchAdminCustomers, type AdminCustomerSummaryDto } from '@/features/admin/customers/api';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import {
  AdminListState,
  AdminTableShell,
  AdminTableSkeleton,
} from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { adminCustomerQueryKeys } from '@/features/admin/customers/queryKeys';
import { normalizePhoneLink } from '@/features/admin/customers/components/customerDetailShared';
import type { PaginatedResult } from '@/shared/types/api';

type SortKey = 'recent_order' | 'highest_spent' | 'most_orders' | 'newest_account' | 'name_asc';

export const AdminCustomersListPage = () => {
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState<SortKey>('recent_order');
  const [page, setPage] = useState(1);
  const customersQuery = useQuery<PaginatedResult<AdminCustomerSummaryDto>, Error>({
    queryKey: [...adminCustomerQueryKeys.list(search, sort), { page }],
    queryFn: () => fetchAdminCustomers(search, sort, page, 10),
  });
  const customers = customersQuery.data?.items ?? [];
  const customersMeta = customersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const status = customersQuery.isLoading ? 'loading' : customersQuery.isError ? 'error' : 'success';
  const error = customersQuery.error?.message ?? null;

  const verifiedCount = useMemo(
    () => customers.filter((customer) => customer.isVerified).length,
    [customers],
  );
  const sortOptions = useMemo(
    () => [
      { value: 'recent_order', label: 'Dernière commande' },
      { value: 'highest_spent', label: 'Plus gros CA' },
      { value: 'most_orders', label: 'Plus de commandes' },
      { value: 'newest_account', label: 'Comptes récents' },
      { value: 'name_asc', label: 'Nom A → Z' },
    ],
    [],
  );
  const onSearchChange = useCallback((value: string) => setSearch(value), [setSearch]);
  const onSortChange = useCallback((value: string) => setSort(value as SortKey), [setSort]);

  useEffect(() => {
    setPage(1);
  }, [search, sort]);

  return (
    <PageContainer size="admin" title="Clients">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {customersMeta.total} client{customersMeta.total > 1 ? 's' : ''} affiché
          {customersMeta.total > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
          Recherche par prénom, nom, email, téléphone ou numéro de commande. {verifiedCount} compte
          {verifiedCount > 1 ? 's' : ''} vérifié{verifiedCount > 1 ? 's' : ''}.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={onSearchChange}
          placeholder="Rechercher un client, un email, un téléphone, une commande..."
        />
        <SelectFilter
          value={sort}
          onChange={onSortChange}
          options={sortOptions}
          ariaLabel="Tri clients"
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={status === 'loading'}
        isEmpty={status === 'success' && customers.length === 0}
        loadingLabel="Chargement..."
        loadingSkeleton={<AdminTableSkeleton columns={6} rows={10} />}
        emptyLabel="Aucun client trouvé."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Client</th>
                <th scope="col">Contact</th>
                <th scope="col">Commandes</th>
                <th scope="col">Total dépensé</th>
                <th scope="col">Dernière commande</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((customer) => (
                <tr key={customer.id}>
                  <th scope="row">
                    <div className="font-semibold text-brand-900">
                      {customer.firstName} {customer.lastName}
                    </div>
                    <div className="muted">
                      {customer.isVerified ? 'Compte vérifié' : 'Compte non vérifié'}
                    </div>
                    {customer.adminTags.length > 0 ? (
                      <div className="mt-2 flex flex-wrap gap-2">
                        {customer.adminTags.slice(0, 3).map((tag) => (
                          <span
                            key={tag}
                            className="rounded-full bg-brand-50 px-2 py-1 text-xs text-stone-700"
                          >
                            {tag}
                          </span>
                        ))}
                      </div>
                    ) : null}
                  </th>
                  <td>
                    <div>{customer.email}</div>
                    <div className="muted">{customer.phoneNumber}</div>
                  </td>
                  <td>{customer.ordersCount}</td>
                  <td>{formatEuroCents(customer.totalSpentCents)}</td>
                  <td>
                    {customer.lastOrderAt ? (
                      formatOptionalFrenchDateTime(customer.lastOrderAt)
                    ) : (
                      <span className="text-xs text-stone-500">Aucune commande</span>
                    )}
                  </td>
                  <td>
                    <div className="flex flex-wrap gap-3">
                      <Link
                        className="inline-flex items-center text-sm font-semibold underline"
                        to={`/admin/customers/${customer.id}`}
                      >
                        Fiche client
                      </Link>
                      {customer.ordersCount > 0 ? (
                        <Link
                          className="inline-flex items-center text-sm underline"
                          to={`/admin/orders?search=${encodeURIComponent(customer.email)}`}
                        >
                          Ses commandes
                        </Link>
                      ) : null}
                      <Link
                        className="inline-flex items-center text-sm underline"
                        to={`/admin/customers/${customer.id}?panel=email`}
                      >
                        Email
                      </Link>
                      {customer.phoneNumber ? (
                        <a
                          className="inline-flex items-center text-sm underline"
                          href={`tel:${normalizePhoneLink(customer.phoneNumber)}`}
                        >
                          Appeler
                        </a>
                      ) : null}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
        <PaginationControls
          page={customersMeta.page}
          total={customersMeta.total}
          totalLabel="client"
          totalPages={customersMeta.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
