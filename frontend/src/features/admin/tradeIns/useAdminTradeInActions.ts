import { type Dispatch, type SetStateAction } from 'react';

import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  adminCloseTradeIn,
  adminDeleteTradeIn,
  adminDownloadTradeInDocument,
  adminFetchTradeIn,
  adminSetTradeInOffer,
  adminSetTradeInStatus,
} from '@/features/tradeIns/publicApi';
import type { TradeInDto } from '@/features/tradeIns/publicApi';
import {
  createTradeInModalState,
  toTradeInRoundedCents,
  type TradeInModalState,
} from './tradeInAdminModalState';

export const useAdminTradeInActions = ({
  closeModal,
  load,
  modalState,
  setError,
  setModalState,
}: {
  closeModal: () => void;
  load: () => Promise<void>;
  modalState: TradeInModalState;
  setError: Dispatch<SetStateAction<string | null>>;
  setModalState: Dispatch<SetStateAction<TradeInModalState>>;
}) => {
  const confirm = useConfirm();
  const toast = useToast();

  const select = async (id: number) => {
    setError(null);
    try {
      const detail = await adminFetchTradeIn(id);
      setModalState(createTradeInModalState(detail));
    } catch (selectError) {
      setError(getHttpErrorMessage(selectError));
    }
  };

  const saveOffer = async () => {
    const { selected, offer, note } = modalState;
    if (!selected || !offer) return;
    try {
      await adminSetTradeInOffer(selected.id, toTradeInRoundedCents(offer), note);
      closeModal();
      toast.show(`Offre enregistrée pour ${selected.reference}.`, { variant: 'success' });
      await load();
    } catch (saveError) {
      setError(getHttpErrorMessage(saveError));
    }
  };

  const changeStatus = async () => {
    const { selected, pendingStatus } = modalState;
    if (!selected || !pendingStatus || pendingStatus === selected.status) return;
    try {
      await adminSetTradeInStatus(selected.id, pendingStatus);
      closeModal();
      toast.show(`Statut de ${selected.reference} mis à jour.`, { variant: 'success' });
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
      if (modalState.selected?.id === request.id) closeModal();
      toast.show(response.message ?? 'La demande de reprise a bien été supprimée.', {
        variant: 'success',
      });
      await load();
    } catch (deleteError) {
      setError(getHttpErrorMessage(deleteError));
    }
  };

  const closeTradeIn = async () => {
    const {
      selected,
      finalOffer,
      paymentMethod,
      paymentStatus,
      transactionReference,
      closureNote,
    } = modalState;
    if (!selected || !finalOffer || !paymentMethod || !paymentStatus) return;
    try {
      const response = await adminCloseTradeIn(selected.id, {
        finalOfferCents: toTradeInRoundedCents(finalOffer),
        paymentMethod,
        paymentStatus,
        transactionReference,
        note: closureNote,
      });
      closeModal();
      toast.show(
        response.message ?? 'La reprise a été clôturée et le justificatif a été généré.',
        { variant: 'success' },
      );
      await load();
    } catch (closeError) {
      setError(getHttpErrorMessage(closeError));
    }
  };

  const downloadDocument = async (document: 'rib' | 'receipt') => {
    if (!modalState.selected) return;
    try {
      const blob = await adminDownloadTradeInDocument(modalState.selected.id, document);
      downloadBlob(
        blob,
        document === 'rib' ? 'rib-demandeur.pdf' : 'justificatif-reprise.pdf',
      );
    } catch (downloadError) {
      setError(getHttpErrorMessage(downloadError));
    }
  };

  return {
    changeStatus,
    closeTradeIn,
    deleteRequest,
    downloadDocument,
    saveOffer,
    select,
  };
};
