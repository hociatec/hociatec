import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  fetchAdminLoyaltyCustomers,
  updateAdminLoyaltyCustomer,
  type AdminLoyaltyCustomerDto,
} from '@/features/loyalty/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import {
  AdminListState,
  AdminMetricCard,
  AdminMetricGrid,
  AdminTableShell,
} from '@/shared/components/admin/AdminDataView';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { useToast } from '@/shared/components/ui/toast';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatFrenchNumber } from '@/shared/lib/formatters';
import { normalizeLoyaltyPoints } from '@/shared/lib/loyalty';
import { AdminLoyaltyCustomerRow } from '@/features/admin/loyalty/components/AdminLoyaltyCustomerRow';
import { LoyaltyBalanceDialog } from '@/features/admin/loyalty/components/LoyaltyBalanceDialog';
import { adminLoyaltyQueryKeys } from '@/features/admin/loyalty/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNonNegativeInteger } from '@/shared/lib/parsers';

export const AdminLoyaltyPage = () => {
  const toast = useToast();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [selectedCustomer, setSelectedCustomer] = useState<AdminLoyaltyCustomerDto | null>(null);
  const [draftPoints, setDraftPoints] = useState('');
  const customersQuery = useQuery<PaginatedResult<AdminLoyaltyCustomerDto>, Error>({
    queryKey: [...adminLoyaltyQueryKeys.customers(search), { page }],
    queryFn: () => fetchAdminLoyaltyCustomers(search, page, 10),
  });
  const items = customersQuery.data?.items ?? [];
  const itemsMeta = customersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const updateMutation = useMutation({
    mutationFn: ({ customerId, points }: { customerId: number; points: number }) =>
      updateAdminLoyaltyCustomer(customerId, points),
    onSuccess: (customer) => {
      queryClient.setQueryData<PaginatedResult<AdminLoyaltyCustomerDto>>(
        [...adminLoyaltyQueryKeys.customers(search), { page }],
        (current) =>
          current
            ? {
                ...current,
                items: current.items.map((item) => (item.id === customer.data.id ? customer.data : item)),
              }
            : current,
      );
      setSelectedCustomer(null);
      setDraftPoints('');
      toast.show(customer.message ?? 'Le solde fidélité a bien été mis à jour.', {
        variant: 'success',
      });
    },
    onError: (error) => {
      toast.show(getHttpErrorMessage(error, 'Impossible de mettre à jour ce solde.'), {
        variant: 'error',
      });
    },
  });

  useEffect(() => {
    if (customersQuery.error) {
      toast.show(getHttpErrorMessage(customersQuery.error, 'Impossible de charger les soldes fidélité.'), {
        variant: 'error',
      });
    }
  }, [customersQuery.error, toast]);

  useEffect(() => {
    setPage(1);
  }, [search]);

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

  const openBalanceDialog = (customer: AdminLoyaltyCustomerDto) => {
    setSelectedCustomer(customer);
    setDraftPoints(String(customer.points));
  };

  const closeBalanceDialog = () => {
    if (updateMutation.isPending) {
      return;
    }

    setSelectedCustomer(null);
    setDraftPoints('');
  };

  const save = (event: FormEvent) => {
    event.preventDefault();
    if (!selectedCustomer) {
      return;
    }

    const points = parseNonNegativeInteger(draftPoints, 0);
    updateMutation.mutate({ customerId: selectedCustomer.id, points });
  };

  return (
    <PageContainer size="admin" title="Fidélité">
      <AdminMetricGrid>
        <AdminMetricCard label="Clients affichés" value={itemsMeta.total} />
        <AdminMetricCard label="Points en circulation" value={formatFrenchNumber(totals.points)} />
        <AdminMetricCard label="Valeur convertible" value={formatEuroCents(totals.euroCents)} />
      </AdminMetricGrid>

      <div className="mb-5">
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher un client..." />
      </div>

      <AdminListState
        loading={customersQuery.isLoading}
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
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
                  {items.map((customer) => (
                    <AdminLoyaltyCustomerRow
                      key={customer.id}
                      customer={customer}
                      onAdjust={openBalanceDialog}
                    />
                  ))}
            </tbody>
          </table>
        </AdminTableShell>
        <PaginationControls
          page={itemsMeta.page}
          total={itemsMeta.total}
          totalLabel="client"
          totalPages={itemsMeta.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>

      <LoyaltyBalanceDialog
        customer={selectedCustomer}
        draftPoints={draftPoints}
        isPending={updateMutation.isPending}
        onClose={closeBalanceDialog}
        onDraftPointsChange={setDraftPoints}
        onSubmit={save}
        toEuroCents={normalizeLoyaltyPoints}
      />
    </PageContainer>
  );
};
