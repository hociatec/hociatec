import { useEffect, useMemo, useState } from 'react';

import {
  fetchAdminLoyaltyCustomers,
  updateAdminLoyaltyCustomer,
  type AdminLoyaltyCustomerDto,
} from '@/features/loyalty/api/loyaltyApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import {
  AdminListState,
  AdminMetricCard,
  AdminMetricGrid,
  AdminTableShell,
} from '@/shared/components/admin/AdminDataView';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { useToast } from '@/shared/components/ui/toast';
import { formatEuroCents, formatFrenchNumber } from '@/shared/lib/formatters';

const pointsToEuroCents = (points: number) => Math.floor(Math.max(0, points) / 100) * 100;

export const AdminLoyaltyPage = () => {
  const toast = useToast();
  const [items, setItems] = useState<AdminLoyaltyCustomerDto[]>([]);
  const [search, setSearch] = useState('');
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');

  useEffect(() => {
    setStatus('loading');
    void fetchAdminLoyaltyCustomers(search)
      .then((rows) => {
        setItems(rows);
        setDrafts(Object.fromEntries(rows.map((row) => [row.id, String(row.points)])));
        setStatus('success');
      })
      .catch((error: unknown) => {
        toast.show(
          error instanceof Error ? error.message : 'Impossible de charger les soldes fidélité.',
          {
            variant: 'error',
          },
        );
        setStatus('error');
      });
  }, [search, toast]);

  const totals = useMemo(
    () =>
      items.reduce(
        (acc, item) => ({
          points: acc.points + item.points,
          euroCents: acc.euroCents + item.euroCents,
        }),
        { points: 0, euroCents: 0 },
      ),
    [items],
  );

  const save = (customerId: number) => {
    const points = Math.max(0, Number.parseInt(drafts[customerId] ?? '0', 10) || 0);
    void updateAdminLoyaltyCustomer(customerId, points)
      .then((customer) => {
        setItems((current) => current.map((item) => (item.id === customer.data.id ? customer.data : item)));
        setDrafts((current) => ({ ...current, [customer.data.id]: String(customer.data.points) }));
        toast.show(customer.message ?? 'Le solde fidélité a bien été mis à jour.', { variant: 'success' });
      })
      .catch((error: unknown) => {
        toast.show(
          error instanceof Error ? error.message : 'Impossible de mettre à jour ce solde.',
          {
            variant: 'error',
          },
        );
      });
  };

  return (
    <PageContainer size="admin" title="Fidélité">
      <AdminMetricGrid>
        <AdminMetricCard label="Clients affichés" value={items.length} />
        <AdminMetricCard label="Points en circulation" value={formatFrenchNumber(totals.points)} />
        <AdminMetricCard label="Valeur convertible" value={formatEuroCents(totals.euroCents)} />
      </AdminMetricGrid>

      <div className="mb-5">
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher un client..." />
      </div>

      <AdminListState
        loading={status === 'loading'}
        isEmpty={items.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucun client trouvé."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Client</th>
                <th scope="col">Solde points</th>
                <th scope="col">Équivalent euros</th>
                <th scope="col">Mise à jour</th>
              </tr>
            </thead>
            <tbody>
              {items.map((customer) => {
                const draftPoints = Math.max(
                  0,
                  Number.parseInt(drafts[customer.id] ?? '0', 10) || 0,
                );

                return (
                  <tr key={customer.id}>
                    <th scope="row">
                      <div className="font-semibold text-brand-900">{customer.fullName}</div>
                      <div className="muted">{customer.email}</div>
                    </th>
                    <td>
                      <input
                        type="number"
                        min={0}
                        step={10}
                        value={drafts[customer.id] ?? String(customer.points)}
                        onChange={(event) =>
                          setDrafts((current) => ({
                            ...current,
                            [customer.id]: event.target.value,
                          }))
                        }
                        className="w-32 rounded-xl border border-brand-200 px-3 py-2"
                        aria-label={`Solde fidélité de ${customer.fullName}`}
                      />
                    </td>
                    <td>{formatEuroCents(pointsToEuroCents(draftPoints))}</td>
                    <td>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => save(customer.id)}
                      >
                        Enregistrer
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
