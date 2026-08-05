import { useEffect, useRef, useState } from 'react';

import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
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

type TradeInModalState = {
  selected: TradeInDto | null;
  pendingStatus: TradeInStatus | null;
  offer: string;
  note: string;
  finalOffer: string;
  paymentMethod: string;
  paymentStatus: string;
  transactionReference: string;
  closureNote: string;
};

const initialModalState: TradeInModalState = {
  selected: null,
  pendingStatus: null,
  offer: '',
  note: '',
  finalOffer: '',
  paymentMethod: 'bank_transfer',
  paymentStatus: 'pending',
  transactionReference: '',
  closureNote: '',
};

const generateTransactionReference = (): string => {
  const date = new Date().toISOString().slice(0, 10).replaceAll('-', '');
  const suffix = Math.random().toString(36).slice(2, 8).toUpperCase();

  return `TRX-${date}-${suffix}`;
};

const toMoneyInput = formatEuroInputFromCents;

const toRoundedCents = (value: string) => Math.round(Number(value.replace(',', '.')) * 100);

export const useAdminTradeInsPage = () => {
  const [items, setItems] = useState<TradeInDto[]>([]);
  const [statusFilter, setStatusFilter] = useState<TradeInStatus | ''>('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [modalState, setModalState] = useState<TradeInModalState>(initialModalState);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const confirm = useConfirm();
  const toast = useToast();

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

  useEffect(() => {
    void load();
  }, [statusFilter]);

  useEffect(() => {
    if (!modalState.selected) return;
    closeButtonRef.current?.focus();
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setModalState(initialModalState);
      }
    };
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [modalState.selected]);

  useEffect(() => {
    if (modalState.paymentMethod === 'store_credit') {
      setModalState((current) => ({ ...current, paymentStatus: 'paid' }));
    }
  }, [modalState.paymentMethod]);

  const closeModal = () => setModalState(initialModalState);

  const select = async (id: number) => {
    setError(null);
    try {
      const detail = await adminFetchTradeIn(id);
      setModalState({
        selected: detail,
        pendingStatus: detail.status,
        offer: toMoneyInput(detail.offerCents),
        note: detail.adminNote ?? '',
        finalOffer: toMoneyInput(detail.finalOfferCents ?? detail.offerCents),
        paymentMethod: detail.paymentMethod ?? 'bank_transfer',
        paymentStatus: detail.paymentStatus ?? 'pending',
        transactionReference: detail.transactionReference ?? generateTransactionReference(),
        closureNote: detail.adminNote ?? '',
      });
    } catch (selectError) {
      setError(getHttpErrorMessage(selectError));
    }
  };

  const saveOffer = async () => {
    const { selected, offer, note } = modalState;
    if (!selected || !offer) return;
    try {
      await adminSetTradeInOffer(selected.id, toRoundedCents(offer), note);
      const reference = selected.reference;
      closeModal();
      toast.show(`Offre enregistrée pour ${reference}.`, { variant: 'success' });
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
      const reference = selected.reference;
      closeModal();
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
        finalOfferCents: toRoundedCents(finalOffer),
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
    closeButtonRef,
    closeModal,
    error,
    items,
    loading,
    modalState,
    saveOffer,
    changeStatus,
    closeTradeIn,
    deleteRequest,
    downloadDocument,
    select,
    setClosureNote: (value: string) =>
      setModalState((current) => ({ ...current, closureNote: value })),
    setFinalOffer: (value: string) =>
      setModalState((current) => ({ ...current, finalOffer: value })),
    setNote: (value: string) => setModalState((current) => ({ ...current, note: value })),
    setOffer: (value: string) => setModalState((current) => ({ ...current, offer: value })),
    setPaymentMethod: (value: string) =>
      setModalState((current) => ({ ...current, paymentMethod: value })),
    setPaymentStatus: (value: string) =>
      setModalState((current) => ({ ...current, paymentStatus: value })),
    setPendingStatus: (value: TradeInStatus | null) =>
      setModalState((current) => ({ ...current, pendingStatus: value })),
    setStatusFilter,
    setTransactionReference: (value: string) =>
      setModalState((current) => ({ ...current, transactionReference: value })),
    statusFilter,
  };
};
