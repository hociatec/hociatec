import type { useAdminCustomerVouchers } from '@/features/admin/customers/hooks/useAdminCustomerVouchers';
import { formatEuroCents, formatOptionalFrenchDate, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

type Voucher = ReturnType<typeof useAdminCustomerVouchers>['vouchers'][number];

export const AdminCustomerVoucherHistory = ({ vouchers, onDelete }: { vouchers: Voucher[]; onDelete: (id: number) => Promise<void> }) => {
  const vouchersPagination = useAdminPagination(vouchers);

  return (
    <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
      <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"><div><h2 className="text-lg font-semibold text-brand-900">Historique des bons envoyés</h2><p className="mt-1 text-sm text-stone-500">Retrouve les offres déjà créées pour ce client, leur statut et leur envoi.</p></div><div className="rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-stone-700">{vouchers.length} bon{vouchers.length > 1 ? 's' : ''}</div></div>
      {vouchers.length === 0 ? <p className="text-sm text-stone-500">Aucun bon de réduction créé pour ce client.</p> : <div className="space-y-3">{vouchersPagination.paginatedItems.map((voucher) => <div key={voucher.id} className="rounded-xl border border-brand-100 bg-brand-50 p-4"><div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"><div><div className="font-semibold text-brand-900">{voucher.name}</div><div className="text-sm text-stone-600">Code {voucher.code} · {voucher.discountType === 'percent' ? `${voucher.discountValue}%` : formatEuroCents(voucher.discountValue)}</div><div className="text-sm text-stone-500">Créé le {formatOptionalFrenchDateTime(voucher.createdAt)}{voucher.sentAt ? ` · envoyé le ${formatOptionalFrenchDateTime(voucher.sentAt)}` : ' · non envoyé'}</div>{voucher.description ? <div className="mt-1 text-sm text-stone-600">{voucher.description}</div> : null}</div><div className="flex flex-wrap gap-2 text-xs"><span className={`rounded-full px-3 py-1 ${voucher.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-100 text-stone-700'}`}>{voucher.isActive ? 'Actif' : 'Inactif'}</span>{voucher.endsAt ? <span className="rounded-full bg-white px-3 py-1 text-stone-600">Fin {formatOptionalFrenchDate(voucher.endsAt)}</span> : null}<button type="button" className="rounded-full bg-red-100 px-3 py-1 text-red-700 transition hover:bg-red-200" onClick={() => void onDelete(voucher.id)}>Supprimer</button></div></div></div>)}</div>}
      <PaginationControls
        page={vouchersPagination.page}
        total={vouchersPagination.total}
        totalLabel="bon"
        totalPages={vouchersPagination.totalPages}
        onPageChange={vouchersPagination.setPage}
      />
    </section>
  );
};
