import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Pencil, Save, X } from 'lucide-react';

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
import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import { useToast } from '@/shared/components/ui/toast';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatFrenchNumber } from '@/shared/lib/formatters';
import { adminLoyaltyQueryKeys } from '@/features/admin/loyalty/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

const pointsToEuroCents = (points: number) => Math.floor(Math.max(0, points) / 100) * 100;

type LoyaltyBalanceDialogProps = {
  customer: AdminLoyaltyCustomerDto | null;
  draftPoints: string;
  isPending: boolean;
  onClose: () => void;
  onDraftPointsChange: (value: string) => void;
  onSubmit: (event: FormEvent) => void;
};

const LoyaltyBalanceDialog = ({
  customer,
  draftPoints,
  isPending,
  onClose,
  onDraftPointsChange,
  onSubmit,
}: LoyaltyBalanceDialogProps) => {
  const parsedPoints = Math.max(0, Number.parseInt(draftPoints, 10) || 0);

  return (
    <Dialog open={customer !== null} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="flex items-center justify-between border-b border-stone-200 pb-4">
            <div>
              <DialogTitle className="text-xl font-bold text-stone-900">
                Ajuster le solde fidélité
              </DialogTitle>
              {customer ? (
                <DialogDescription className="mt-0.5 text-sm text-stone-500">
                  {customer.fullName} · {customer.email}
                </DialogDescription>
              ) : null}
            </div>
            <button
              type="button"
              className="rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
              onClick={onClose}
              aria-label="Fermer la fenêtre"
            >
              <X size={20} />
            </button>
          </header>

          <form onSubmit={onSubmit} className="mt-6 space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3">
                <span className="block text-sm text-stone-500">Solde actuel</span>
                <strong className="mt-1 block text-xl text-brand-900">
                  {formatFrenchNumber(customer?.points ?? 0)} pts
                </strong>
              </div>
              <div className="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3">
                <span className="block text-sm text-stone-500">Valeur actuelle</span>
                <strong className="mt-1 block text-xl text-brand-900">
                  {formatEuroCents(customer?.euroCents ?? 0)}
                </strong>
              </div>
            </div>

            <div className="space-y-2">
              <label htmlFor="loyalty-points" className="block text-sm font-medium text-stone-800">
                Nouveau solde
              </label>
              <input
                id="loyalty-points"
                type="number"
                min={0}
                step={10}
                value={draftPoints}
                onChange={(event) => onDraftPointsChange(event.target.value)}
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                autoFocus
              />
            </div>

            <div className="rounded-lg border border-stone-200 px-4 py-3 text-sm text-stone-600">
              Équivalent après mise à jour :{' '}
              <strong className="text-brand-900">{formatEuroCents(pointsToEuroCents(parsedPoints))}</strong>
            </div>

            <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={onClose}
                disabled={isPending}
                className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                Annuler
              </button>
              <button
                type="submit"
                disabled={isPending || customer === null}
                className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
              >
                <Save size={16} aria-hidden="true" />
                {isPending ? 'Enregistrement...' : 'Enregistrer'}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};

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
      toast.show(error instanceof Error ? error.message : 'Impossible de mettre à jour ce solde.', {
        variant: 'error',
      });
    },
  });

  useEffect(() => {
    if (customersQuery.error) {
      toast.show(customersQuery.error.message || 'Impossible de charger les soldes fidélité.', {
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

    const points = Math.max(0, Number.parseInt(draftPoints, 10) || 0);
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
                <tr key={customer.id}>
                  <th scope="row">
                    <div className="font-semibold text-brand-900">{customer.fullName}</div>
                    <div className="muted">{customer.email}</div>
                  </th>
                  <td>{formatFrenchNumber(customer.points)} pts</td>
                  <td>{formatEuroCents(customer.euroCents)}</td>
                  <td>
                    <button
                      type="button"
                      className="catalog-admin-actions__edit inline-flex items-center gap-2"
                      onClick={() => openBalanceDialog(customer)}
                    >
                      <Pencil size={16} aria-hidden="true" />
                      Ajuster
                    </button>
                  </td>
                </tr>
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
      />
    </PageContainer>
  );
};
