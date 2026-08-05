import { useEffect, useState } from 'react';

import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminFetchTradeIns } from '@/features/tradeIns/publicApi';
import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/publicApi';
import { initialTradeInModalState, useTradeInAdminModalState } from './tradeInAdminModalState';
import { useAdminTradeInActions } from './useAdminTradeInActions';

export const useAdminTradeInsPage = () => {
  const [items, setItems] = useState<TradeInDto[]>([]);
  const [statusFilter, setStatusFilter] = useState<TradeInStatus | ''>('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const {
    closeButtonRef,
    closeModal,
    modalState,
    setClosureNote,
    setFinalOffer,
    setModalState,
    setNote,
    setOffer,
    setPaymentMethod,
    setPaymentStatus,
    setPendingStatus,
    setTransactionReference,
  } = useTradeInAdminModalState();
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
        setModalState(initialTradeInModalState);
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
  const actions = useAdminTradeInActions({
    closeModal,
    load,
    modalState,
    setError,
    setModalState,
  });

  return {
    closeButtonRef,
    closeModal,
    error,
    items,
    loading,
    modalState,
    ...actions,
    setClosureNote,
    setFinalOffer,
    setNote,
    setOffer,
    setPaymentMethod,
    setPaymentStatus,
    setPendingStatus,
    setStatusFilter,
    setTransactionReference,
    statusFilter,
  };
};
