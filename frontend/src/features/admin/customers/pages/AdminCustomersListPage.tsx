import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';

import { fetchAdminCustomers, type AdminCustomerSummaryDto } from '@/features/admin/customers/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type SortKey = 'recent_order' | 'highest_spent' | 'most_orders' | 'newest_account' | 'name_asc';

const normalizePhoneLink = (phoneNumber: string) => phoneNumber.replace(/[^+\d]/g, '');

export const AdminCustomersListPage = () => {
  const [customers, setCustomers] = useState<AdminCustomerSummaryDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState<SortKey>('recent_order');

  useEffect(() => {
    setStatus('loading');
    setError(null);

    void fetchAdminCustomers(search, sort)
      .then((items) => {
        setCustomers(items);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Impossible de charger les clients');
        setStatus('error');
      });
  }, [search, sort]);

  const verifiedCount = useMemo(
    () => customers.filter((customer) => customer.isVerified).length,
    [customers],
  );

  return (
    <PageContainer size="admin" title="Clients">
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {customers.length} client{customers.length > 1 ? 's' : ''} affiché
          {customers.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
          Recherche par prénom, nom, email, téléphone ou numéro de commande. {verifiedCount} compte
          {verifiedCount > 1 ? 's' : ''} vérifié{verifiedCount > 1 ? 's' : ''}.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher un client, un email, un téléphone, une commande..."
        />
        <SelectFilter
          value={sort}
          onChange={(value) => setSort(value as SortKey)}
          options={[
            { value: 'recent_order', label: 'Dernière commande' },
            { value: 'highest_spent', label: 'Plus gros CA' },
            { value: 'most_orders', label: 'Plus de commandes' },
            { value: 'newest_account', label: 'Comptes récents' },
            { value: 'name_asc', label: 'Nom A → Z' },
          ]}
          ariaLabel="Tri clients"
        />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={status === 'loading'}
        isEmpty={status === 'success' && customers.length === 0}
        loadingLabel="Chargement..."
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
      </AdminListState>
    </PageContainer>
  );
};
