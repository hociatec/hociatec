import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteVoucher, fetchVouchers, type Voucher } from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents } from '@/shared/lib/formatters';

export const VouchersPage = () => {
  useDocumentTitle('Admin - Bons de réduction');
  const toast = useToast();
  const confirm = useConfirm();
  const [vouchers, setVouchers] = useState<Voucher[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchVouchers()
      .then((items) => setVouchers(items))
      .catch((err) => {
        const message = getHttpErrorMessage(err, 'Impossible de charger les bons.');
        setError(message);
        toast.show(message, { variant: 'error' });
      })
      .finally(() => setLoading(false));
  }, [toast]);

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

    try {
      const response = await deleteVoucher(voucherId);
      setVouchers((current) => current.filter((item) => item.id !== voucherId));
      toast.show(response.message ?? 'Le bon de réduction a bien été supprimé.', { variant: 'success' });
    } catch (err) {
      const message = getHttpErrorMessage(err, 'Suppression impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    }
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

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={loading}
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
      </AdminListState>
    </PageContainer>
  );
};
