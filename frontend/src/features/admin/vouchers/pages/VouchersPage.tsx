import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useState } from 'react';
import { Link } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { deleteVoucher, fetchVouchers, type Voucher } from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents } from '@/shared/lib/formatters';
import { adminVoucherQueryKeys } from '@/shared/lib/queryKeys';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';

export const VouchersPage = () => {
  useDocumentTitle('Admin - Bons de réduction');
  const toast = useToast();
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const vouchersQuery = useQuery<PaginatedResult<Voucher>, Error>({
    queryKey: [...adminVoucherQueryKeys.list(), { page }],
    queryFn: () => fetchVouchers(page, 10),
  });
  const vouchers = vouchersQuery.data?.items ?? [];
  const pagination = vouchersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const deleteMutation = useMutation({
    mutationFn: deleteVoucher,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminVoucherQueryKeys.list() });
      toast.show(response.message ?? 'Le bon de réduction a bien été supprimé.', {
        variant: 'success',
      });
    },
    onError: (err) => {
      const message = getHttpErrorMessage(err, 'Suppression impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    },
  });

  const handleDelete = async (voucherId: number) => {
    const voucher = vouchers.find((item) => item.id === voucherId);
    const voucherLabel = voucher ? `"${voucher.name}" (${voucher.code})` : 'ce bon de reduction';

    const confirmed = await confirm({
      title: 'Supprimer le bon',
      description: `Supprimer ${voucherLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) return;

    deleteMutation.mutate(voucherId);
  };

  return (
    <PageContainer
      size="admin"
      title="Bons de réduction"
      headerActions={<PrimaryLink to="/admin/vouchers/new">Créer un bon</PrimaryLink>}
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Les bons de réduction sont gérés ici, séparément des promotions automatiques.
        </p>
        <p className="text-sm text-stone-500">
          La liste reste simple: chaque bon est éditable via une page dédiée.
        </p>
      </div>

      {(error || vouchersQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(vouchersQuery.error, 'Impossible de charger les bons.')}
        </FeedbackMessage>
      )}

      <AdminListState
        loading={vouchersQuery.isLoading}
        isEmpty={vouchers.length === 0}
        loadingLabel="Chargement des bons..."
        emptyLabel="Aucun bon de réduction."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Code</th>
                <th scope="col">Remise</th>
                <th scope="col">Statut</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {vouchers.map((voucher) => (
                <tr key={voucher.id}>
                  <th scope="row">{voucher.name}</th>
                  <td>{voucher.code}</td>
                  <td>
                    {voucher.discountType === 'percent'
                      ? `${voucher.discountValue}%`
                      : formatEuroCents(voucher.discountValue)}
                  </td>
                  <td>{voucher.isActive ? 'Actif' : 'Inactif'}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/vouchers/${voucher.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier le bon ${voucher.name} (${voucher.code})`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(voucher.id)}
                        aria-label={`Supprimer le bon ${voucher.name} (${voucher.code})`}
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
        <PaginationControls
          page={pagination.page}
          total={pagination.total}
          totalLabel="bon"
          totalPages={pagination.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
