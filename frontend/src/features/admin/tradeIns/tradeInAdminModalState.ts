import { useRef, useState } from 'react';

import { formatApiDateForDateInput, formatEuroInputFromCents } from '@/shared/lib/formatters';
import { createRandomCodeSuffix } from '@/shared/lib/random';
import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/publicApi';

export type TradeInModalState = {
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

export const initialTradeInModalState: TradeInModalState = {
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

export const generateTradeInTransactionReference = (): string => {
  const date = formatApiDateForDateInput(new Date()).replaceAll('-', '');
  const suffix = createRandomCodeSuffix(3);

  return `TRX-${date}-${suffix}`;
};

export const toTradeInMoneyInput = formatEuroInputFromCents;

export const toTradeInRoundedCents = (value: string) =>
  Math.round(Number(value.replace(',', '.')) * 100);

export const createTradeInModalState = (detail: TradeInDto): TradeInModalState => ({
  selected: detail,
  pendingStatus: detail.status,
  offer: toTradeInMoneyInput(detail.offerCents),
  note: detail.adminNote ?? '',
  finalOffer: toTradeInMoneyInput(detail.finalOfferCents ?? detail.offerCents),
  paymentMethod: detail.paymentMethod ?? 'bank_transfer',
  paymentStatus: detail.paymentStatus ?? 'pending',
  transactionReference: detail.transactionReference ?? generateTradeInTransactionReference(),
  closureNote: detail.adminNote ?? '',
});

export const useTradeInAdminModalState = () => {
  const [modalState, setModalState] = useState<TradeInModalState>(initialTradeInModalState);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const closeModal = () => setModalState(initialTradeInModalState);

  return {
    closeButtonRef,
    closeModal,
    modalState,
    setClosureNote: (value: string) =>
      setModalState((current) => ({ ...current, closureNote: value })),
    setFinalOffer: (value: string) =>
      setModalState((current) => ({ ...current, finalOffer: value })),
    setModalState,
    setNote: (value: string) => setModalState((current) => ({ ...current, note: value })),
    setOffer: (value: string) => setModalState((current) => ({ ...current, offer: value })),
    setPaymentMethod: (value: string) =>
      setModalState((current) => ({ ...current, paymentMethod: value })),
    setPaymentStatus: (value: string) =>
      setModalState((current) => ({ ...current, paymentStatus: value })),
    setPendingStatus: (value: TradeInStatus | null) =>
      setModalState((current) => ({ ...current, pendingStatus: value })),
    setTransactionReference: (value: string) =>
      setModalState((current) => ({ ...current, transactionReference: value })),
  };
};
