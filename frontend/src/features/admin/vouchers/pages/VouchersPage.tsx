import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteVoucher, fetchVouchers, type Voucher } from '@/features/admin/vouchers/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const centsToEuro = (value: number) => (value / 100).toFixed(2);

export const VouchersPage = () => {
  useDocumentTitle('Admin - Bons de réduction');
  const toast = useToast();
  const [vouchers, setVouchers] = useState<Voucher[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchVouchers()
      .then((items) => setVouchers(items))
      .catch((err: any) => {
        const message = err?.message ?? 'Impossible de charger les bons.';
        setError(message);
        toast.show(message, { variant: 'error' });
      })
      .finally(() => setLoading(false));
  }, [toast]);

  const handleDelete = async (voucherId: number) => {
    const voucher = vouchers.find((item) => item.id === voucherId);
    const voucherLabel = voucher ? `"${voucher.name}" (${voucher.code})` : 'ce bon de reduction';

    if (!window.confirm(`Supprimer ${voucherLabel} ?`)) return;

    try {
      await deleteVoucher(voucherId);
      setVouchers((current) => current.filter((item) => item.id !== voucherId));
      toast.show('Bon de réduction supprimé.', { variant: 'success' });
    } catch (err: any) {
      const message = err?.message ?? 'Suppression impossible.';
      setError(message);
      toast.show(message, { variant: 'error' });
    }
  };

  return (
    <PageContainer
      title="Bons de réduction"
      headerActions={
        <Link
          to="/admin/vouchers/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Créer un bon
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          Les bons de réduction sont gérés ici, séparément des promotions automatiques.
        </p>
        <p className="text-sm text-slate-500">
          La liste reste simple: chaque bon est éditable via une page dédiée.
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}

      <div className="overflow-x-auto">
        <table
          className="w-full border-collapse bg-white text-left text-sm text-slate-900"
          aria-busy={loading}
        >
          <thead>
            <tr className="border-b border-slate-300">
              <th scope="col" className="px-3 py-2 font-semibold">Nom</th>
              <th scope="col" className="px-3 py-2 font-semibold">Code</th>
              <th scope="col" className="px-3 py-2 font-semibold">Remise</th>
              <th scope="col" className="px-3 py-2 font-semibold">Statut</th>
              <th scope="col" className="px-3 py-2 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody>
            {!loading && vouchers.length === 0 ? (
              <tr className="border-b border-slate-200">
                <td colSpan={5} className="px-3 py-6 text-center text-slate-600">
                  Aucun bon de réduction.
                </td>
              </tr>
            ) : (
              vouchers.map((voucher) => (
                <tr key={voucher.id} className="border-b border-slate-200">
                  <th scope="row" className="px-3 py-2 font-medium">{voucher.name}</th>
                  <td className="px-3 py-2">{voucher.code}</td>
                  <td className="px-3 py-2">
                    {voucher.discountType === 'percent'
                      ? `${voucher.discountValue}%`
                      : `${centsToEuro(voucher.discountValue)} EUR`}
                  </td>
                  <td className="px-3 py-2">{voucher.isActive ? 'Actif' : 'Inactif'}</td>
                  <td className="px-3 py-2">
                    <Link
                      to={`/admin/vouchers/${voucher.id}/edit`}
                      className="underline"
                      aria-label={`Modifier le bon ${voucher.name} (${voucher.code})`}
                    >
                      Modifier
                    </Link>
                    {' '}
                    <button
                      type="button"
                      className="underline"
                      onClick={() => void handleDelete(voucher.id)}
                      aria-label={`Supprimer le bon ${voucher.name} (${voucher.code})`}
                    >
                      Supprimer
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </PageContainer>
  );
};
