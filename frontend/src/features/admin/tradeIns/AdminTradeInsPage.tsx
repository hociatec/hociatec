import { useEffect, useRef, useState } from 'react';

import { PageContainer } from '@/shared/components/PageContainer';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDate } from '@/shared/lib/formatters';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import {
  adminCloseTradeIn,
  adminDeleteTradeIn,
  adminDownloadTradeInDocument,
  adminFetchTradeIn,
  adminFetchTradeIns,
  adminSetTradeInOffer,
  adminSetTradeInStatus,
} from '@/features/tradeIns/api';
import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/types';
import { useTradeInMetadata } from '@/features/tradeIns/useTradeInMetadata';
import { AdminTradeInDetailsModal } from './AdminTradeInDetailsModal';

const generateTransactionReference = (): string => {
  const date = new Date().toISOString().slice(0, 10).replaceAll('-', '');
  const suffix = Math.random().toString(36).slice(2, 8).toUpperCase();

  return `TRX-${date}-${suffix}`;
};

export const AdminTradeInsPage = () => {
  useDocumentTitle('Reprises matériel');
  const [items, setItems] = useState<TradeInDto[]>([]);
  const [selected, setSelected] = useState<TradeInDto | null>(null);
  const [pendingStatus, setPendingStatus] = useState<TradeInStatus | null>(null);
  const [statusFilter, setStatusFilter] = useState<TradeInStatus | ''>('');
  const [offer, setOffer] = useState('');
  const [note, setNote] = useState('');
  const [finalOffer, setFinalOffer] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('bank_transfer');
  const [paymentStatus, setPaymentStatus] = useState('pending');
  const [transactionReference, setTransactionReference] = useState('');
  const [closureNote, setClosureNote] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const confirm = useConfirm();
  const toast = useToast();
  const { statuses, paymentMethods, paymentStatuses } = useTradeInMetadata();

  const load = async () => {
    setLoading(true);
    try {
      setItems(await adminFetchTradeIns(statusFilter || undefined));
    } catch (loadError) {
      setError(getHttpErrorMessage(loadError));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { void load(); }, [statusFilter]);

  useEffect(() => {
    if (!selected) return;
    closeButtonRef.current?.focus();
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setSelected(null);
    };
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [selected]);

  useEffect(() => {
    if (paymentMethod === 'store_credit') setPaymentStatus('paid');
  }, [paymentMethod]);

  const select = async (id: number) => {
    setError(null);
    try {
      const detail = await adminFetchTradeIn(id);
      setSelected(detail);
      setPendingStatus(detail.status);
      setOffer(detail.offerCents === null ? '' : String(detail.offerCents / 100));
      setNote(detail.adminNote ?? '');
      setFinalOffer(detail.finalOfferCents ? String(detail.finalOfferCents / 100) : detail.offerCents === null ? '' : String(detail.offerCents / 100));
      setPaymentMethod(detail.paymentMethod ?? 'bank_transfer');
      setPaymentStatus(detail.paymentStatus ?? 'pending');
      setTransactionReference(detail.transactionReference ?? generateTransactionReference());
      setClosureNote(detail.adminNote ?? '');
    } catch (selectError) {
      setError(getHttpErrorMessage(selectError));
    }
  };

  const saveOffer = async () => {
    if (!selected || !offer) return;
    try {
      await adminSetTradeInOffer(selected.id, Math.round(Number(offer.replace(',', '.')) * 100), note);
      const reference = selected.reference;
      setSelected(null);
      toast.show(`Offre enregistrée pour ${reference}.`, { variant: 'success' });
      await load();
    } catch (saveError) {
      setError(getHttpErrorMessage(saveError));
    }
  };

  const changeStatus = async () => {
    if (!selected || !pendingStatus || pendingStatus === selected.status) return;
    try {
      await adminSetTradeInStatus(selected.id, pendingStatus);
      const reference = selected.reference;
      setSelected(null);
      toast.show(`Statut de ${reference} mis à jour.`, { variant: 'success' });
      await load();
    } catch (statusError) {
      setError(getHttpErrorMessage(statusError));
    }
  };

  const deleteRequest = async (request: TradeInDto) => {
    const confirmed = await confirm({
      title: 'Supprimer cette demande ?',
      description: 'Cette demande sera supprimée définitivement. Cette action est irréversible.',
      confirmLabel: 'Supprimer définitivement',
      cancelLabel: 'Conserver',
    });
    if (!confirmed) return;
    try {
      const response = await adminDeleteTradeIn(request.id);
      if (selected?.id === request.id) setSelected(null);
      toast.show(response.message ?? 'La demande de reprise a bien été supprimée.', { variant: 'success' });
      await load();
    } catch (deleteError) {
      setError(getHttpErrorMessage(deleteError));
    }
  };

  const closeTradeIn = async () => {
    if (!selected || !finalOffer || !paymentMethod || !paymentStatus) return;
    try {
      const response = await adminCloseTradeIn(selected.id, {
        finalOfferCents: Math.round(Number(finalOffer.replace(',', '.')) * 100),
        paymentMethod,
        paymentStatus,
        transactionReference,
        note: closureNote,
      });
      setSelected(null);
      toast.show(response.message ?? 'La reprise a été clôturée et le justificatif a été généré.', { variant: 'success' });
      await load();
    } catch (closeError) {
      setError(getHttpErrorMessage(closeError));
    }
  };

  const downloadDocument = async (document: 'rib' | 'receipt') => {
    if (!selected) return;
    try {
      const blob = await adminDownloadTradeInDocument(selected.id, document);
      downloadBlob(blob, document === 'rib' ? 'rib-demandeur.pdf' : 'justificatif-reprise.pdf');
    } catch (downloadError) {
      setError(getHttpErrorMessage(downloadError));
    }
  };

  return (
    <PageContainer size="wide" title="Reprises de matériel">
      <div className="space-y-5">
        {error && <ErrorState>{error}</ErrorState>}
        <label className="register-form__field max-w-xs">
          <span>Filtrer par statut</span>
          <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value as TradeInStatus | '')}>
            <option value="">Toutes</option>
            {statuses.map(({ value, label }) => <option key={value} value={value}>{label}</option>)}
          </select>
        </label>
        {loading ? <LoadingState>Chargement des demandes…</LoadingState> : (
          <div className="overflow-auto rounded border border-brand-100">
            <table className="w-full text-left text-sm">
              <caption className="sr-only">Demandes de reprise de matériel</caption>
              <thead><tr className="border-b bg-stone-50"><th className="p-3">Référence</th><th className="p-3">Matériel</th><th className="p-3">État</th><th className="p-3">Statut</th><th className="p-3">Date</th><th className="p-3"><span className="sr-only">Action</span></th></tr></thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id} className="border-b hover:bg-stone-50">
                    <th scope="row" className="p-3 font-medium">{item.reference}</th>
                    <td className="p-3">{item.productName}</td>
                    <td className="p-3">{item.conditionLabel}</td>
                    <td className="p-3">{item.statusLabel}</td>
                    <td className="p-3">{formatFrenchDate(item.createdAt) ?? '—'}</td>
                    <td className="p-3"><div className="flex flex-wrap gap-2"><button type="button" className="register-form__submit" onClick={() => void select(item.id)}>Voir la demande</button><button type="button" className="rounded border border-red-200 px-3 py-2 font-semibold text-red-700 hover:bg-red-50" aria-label="Supprimer la demande" onClick={() => void deleteRequest(item)}>Supprimer</button></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
            {items.length === 0 && <p className="p-4">Aucune demande.</p>}
          </div>
        )}
      </div>
      {selected && <AdminTradeInDetailsModal
        selected={selected}
        closeButtonRef={closeButtonRef}
        pendingStatus={pendingStatus}
        offer={offer}
        note={note}
        finalOffer={finalOffer}
        paymentMethod={paymentMethod}
        paymentStatus={paymentStatus}
        transactionReference={transactionReference}
        closureNote={closureNote}
        onClose={() => setSelected(null)}
        onOfferChange={setOffer}
        onNoteChange={setNote}
        onStatusChange={setPendingStatus}
        onPaymentMethodChange={setPaymentMethod}
        onPaymentStatusChange={setPaymentStatus}
        onFinalOfferChange={setFinalOffer}
        onTransactionReferenceChange={setTransactionReference}
        onClosureNoteChange={setClosureNote}
        onSaveOffer={() => void saveOffer()}
        onChangeStatus={() => void changeStatus()}
        onDelete={() => void deleteRequest(selected)}
        onCloseTradeIn={() => void closeTradeIn()}
        onDownloadDocument={(document) => void downloadDocument(document)}
        paymentMethods={paymentMethods}
        paymentStatuses={paymentStatuses}
      />}
    </PageContainer>
  );
};
