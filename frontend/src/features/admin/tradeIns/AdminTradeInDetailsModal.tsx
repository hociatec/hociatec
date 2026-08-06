import type { RefObject } from 'react';

import { Dialog, DialogBackdrop, DialogPanel } from '@/shared/components/ui/dialog';
import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/publicApi';
import {
  AdminTradeInClosureSection,
  AdminTradeInHeader,
  AdminTradeInOfferSection,
  AdminTradeInSummarySections,
} from './AdminTradeInDetailsSections';

type PaymentOption = { value: string; label: string };

type AdminTradeInDetailsModalProps = {
  selected: TradeInDto;
  closeButtonRef: RefObject<HTMLButtonElement | null>;
  pendingStatus: TradeInStatus | null;
  offer: string;
  note: string;
  finalOffer: string;
  paymentMethod: string;
  paymentStatus: string;
  transactionReference: string;
  closureNote: string;
  onClose: () => void;
  onOfferChange: (value: string) => void;
  onNoteChange: (value: string) => void;
  onStatusChange: (value: TradeInStatus) => void;
  onPaymentMethodChange: (value: string) => void;
  onPaymentStatusChange: (value: string) => void;
  onFinalOfferChange: (value: string) => void;
  onTransactionReferenceChange: (value: string) => void;
  onClosureNoteChange: (value: string) => void;
  onSaveOffer: () => void;
  onChangeStatus: () => void;
  onDelete: () => void;
  onCloseTradeIn: () => void;
  onDownloadDocument: (document: 'rib' | 'receipt') => void;
  paymentMethods: PaymentOption[];
  paymentStatuses: PaymentOption[];
};

export const AdminTradeInDetailsModal = ({
  selected,
  closeButtonRef,
  pendingStatus,
  offer,
  note,
  finalOffer,
  paymentMethod,
  paymentStatus,
  transactionReference,
  closureNote,
  onClose,
  onOfferChange,
  onNoteChange,
  onStatusChange,
  onPaymentMethodChange,
  onPaymentStatusChange,
  onFinalOfferChange,
  onTransactionReferenceChange,
  onClosureNoteChange,
  onSaveOffer,
  onChangeStatus,
  onDelete,
  onCloseTradeIn,
  onDownloadDocument,
  paymentMethods,
  paymentStatuses,
}: AdminTradeInDetailsModalProps) => (
  <Dialog open onClose={onClose} className="relative z-50">
    <DialogBackdrop className="fixed inset-0 bg-black/60" />
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <DialogPanel className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
        <AdminTradeInHeader selected={selected} onClose={onClose} closeButtonRef={closeButtonRef} />

        <div className="mt-5 space-y-5">
          <AdminTradeInSummarySections selected={selected} />
          <AdminTradeInOfferSection
            offer={offer}
            note={note}
            pendingStatus={pendingStatus}
            selected={selected}
            onOfferChange={onOfferChange}
            onNoteChange={onNoteChange}
            onStatusChange={onStatusChange}
            onSaveOffer={onSaveOffer}
            onChangeStatus={onChangeStatus}
            onDelete={onDelete}
          />
          <AdminTradeInClosureSection
            selected={selected}
            finalOffer={finalOffer}
            paymentMethod={paymentMethod}
            paymentStatus={paymentStatus}
            transactionReference={transactionReference}
            closureNote={closureNote}
            paymentMethods={paymentMethods}
            paymentStatuses={paymentStatuses}
            onFinalOfferChange={onFinalOfferChange}
            onPaymentMethodChange={onPaymentMethodChange}
            onPaymentStatusChange={onPaymentStatusChange}
            onTransactionReferenceChange={onTransactionReferenceChange}
            onClosureNoteChange={onClosureNoteChange}
            onCloseTradeIn={onCloseTradeIn}
            onDownloadDocument={onDownloadDocument}
          />
        </div>
      </DialogPanel>
    </div>
  </Dialog>
);
