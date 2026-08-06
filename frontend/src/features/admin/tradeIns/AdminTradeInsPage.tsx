import { PageContainer } from '@/shared/components/layout/PageContainer';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDate } from '@/shared/lib/formatters';
import type { TradeInStatus } from '@/features/tradeIns/publicApi';
import { useTradeInMetadata } from '@/features/tradeIns/publicApi';
import { AdminTradeInDetailsModal } from './AdminTradeInDetailsModal';
import { useAdminTradeInsPage } from './useAdminTradeInsPage';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

export const AdminTradeInsPage = () => {
  useDocumentTitle('Reprises matériel');
  const { statuses, paymentMethods, paymentStatuses } = useTradeInMetadata();
  const controller = useAdminTradeInsPage();

  return (
    <PageContainer size="wide" title="Reprises de matériel">
      <div className="space-y-5">
        {controller.error && <ErrorState>{controller.error}</ErrorState>}
        <label className="register-form__field max-w-xs">
          <span>Filtrer par statut</span>
          <select
            value={controller.statusFilter}
            onChange={(event) => controller.setStatusFilter(event.target.value as TradeInStatus | '')}
          >
            <option value="">Toutes</option>
            {statuses.map(({ value, label }) => <option key={value} value={value}>{label}</option>)}
          </select>
        </label>
        {controller.loading ? <LoadingState>Chargement des demandes…</LoadingState> : (
          <div className="overflow-auto rounded border border-brand-100">
            <table className="w-full text-left text-sm">
              <caption className="sr-only">Demandes de reprise de matériel</caption>
              <thead><tr className="border-b bg-stone-50"><th className="p-3">Référence</th><th className="p-3">Matériel</th><th className="p-3">État</th><th className="p-3">Statut</th><th className="p-3">Date</th><th className="p-3"><span className="sr-only">Action</span></th></tr></thead>
              <tbody>
                {controller.items.map((item) => (
                  <tr key={item.id} className="border-b hover:bg-stone-50">
                    <th scope="row" className="p-3 font-medium">{item.reference}</th>
                    <td className="p-3">{item.productName}</td>
                    <td className="p-3">{item.conditionLabel}</td>
                    <td className="p-3">{item.statusLabel}</td>
                    <td className="p-3">{formatFrenchDate(item.createdAt) ?? '—'}</td>
                    <td className="p-3"><div className="flex flex-wrap gap-2"><button type="button" className="register-form__submit" onClick={() => void controller.select(item.id)}>Voir la demande</button><button type="button" className="rounded border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50" aria-label="Supprimer la demande" onClick={() => void controller.deleteRequest(item)}>Supprimer</button></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
            {controller.items.length === 0 && <p className="p-4">Aucune demande.</p>}
            <PaginationControls
              page={controller.pagination.page}
              total={controller.pagination.total}
              totalLabel="demande"
              totalPages={controller.pagination.totalPages}
              onPageChange={controller.setPage}
            />
          </div>
        )}
      </div>
      {controller.modalState.selected && <AdminTradeInDetailsModal
        selected={controller.modalState.selected}
        closeButtonRef={controller.closeButtonRef}
        pendingStatus={controller.modalState.pendingStatus}
        offer={controller.modalState.offer}
        note={controller.modalState.note}
        finalOffer={controller.modalState.finalOffer}
        paymentMethod={controller.modalState.paymentMethod}
        paymentStatus={controller.modalState.paymentStatus}
        transactionReference={controller.modalState.transactionReference}
        closureNote={controller.modalState.closureNote}
        onClose={controller.closeModal}
        onOfferChange={controller.setOffer}
        onNoteChange={controller.setNote}
        onStatusChange={controller.setPendingStatus}
        onPaymentMethodChange={controller.setPaymentMethod}
        onPaymentStatusChange={controller.setPaymentStatus}
        onFinalOfferChange={controller.setFinalOffer}
        onTransactionReferenceChange={controller.setTransactionReference}
        onClosureNoteChange={controller.setClosureNote}
        onSaveOffer={() => void controller.saveOffer()}
        onChangeStatus={() => void controller.changeStatus()}
        onDelete={() => void controller.deleteRequest(controller.modalState.selected!)}
        onCloseTradeIn={() => void controller.closeTradeIn()}
        onDownloadDocument={(document) => void controller.downloadDocument(document)}
        paymentMethods={paymentMethods}
        paymentStatuses={paymentStatuses}
      />}
    </PageContainer>
  );
};
