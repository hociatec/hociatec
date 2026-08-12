import type { TradeInDto } from '@/features/tradeIns/publicApi';
import { PaymentMethodFields, PaymentStatusFields } from '../AdminTradeInDetailFields';

type PaymentOption = { value: string; label: string };

type AdminTradeInClosureSectionProps = {
  selected: TradeInDto;
  finalOffer: string;
  paymentMethod: string;
  paymentStatus: string;
  transactionReference: string;
  closureNote: string;
  paymentMethods: PaymentOption[];
  paymentStatuses: PaymentOption[];
  onFinalOfferChange: (value: string) => void;
  onPaymentMethodChange: (value: string) => void;
  onPaymentStatusChange: (value: string) => void;
  onTransactionReferenceChange: (value: string) => void;
  onClosureNoteChange: (value: string) => void;
  onCloseTradeIn: () => void;
  onDownloadDocument: (document: 'rib' | 'receipt') => void;
};

export const AdminTradeInClosureSection = ({
  selected,
  finalOffer,
  paymentMethod,
  paymentStatus,
  transactionReference,
  closureNote,
  paymentMethods,
  paymentStatuses,
  onFinalOfferChange,
  onPaymentMethodChange,
  onPaymentStatusChange,
  onTransactionReferenceChange,
  onClosureNoteChange,
  onCloseTradeIn,
  onDownloadDocument,
}: AdminTradeInClosureSectionProps) => {
  if (selected.status !== 'inspected' && selected.status !== 'completed') {
    return null;
  }

  return (
    <section
      aria-labelledby="trade-in-close-title"
      className="rounded-lg border border-emerald-200 bg-emerald-50 p-4"
    >
      <h3 id="trade-in-close-title" className="mb-3 text-base font-semibold text-emerald-950">
        Clôture de la reprise
      </h3>
      <div className="space-y-4">
        <label className="register-form__field">
          <span>Montant final (€)</span>
          <input
            type="number"
            min="0.01"
            step="0.01"
            value={finalOffer}
            onChange={(event) => onFinalOfferChange(event.target.value)}
          />
        </label>
        <PaymentMethodFields
          options={paymentMethods}
          value={paymentMethod}
          onChange={onPaymentMethodChange}
        />
        <PaymentStatusFields
          options={paymentStatuses}
          paymentMethod={paymentMethod}
          value={paymentStatus}
          onChange={onPaymentStatusChange}
        />

        <div className="space-y-4 border-t border-emerald-200 pt-5">
          <h4 className="text-sm font-semibold text-stone-800">Traçabilité du règlement</h4>
          <label className="register-form__field">
            <span>Référence de transaction</span>
            <input
              value={transactionReference}
              onChange={(event) => onTransactionReferenceChange(event.target.value)}
              placeholder="Ex. VIR-2026-001"
            />
          </label>
        </div>

        <div className="space-y-4 border-t border-emerald-200 pt-5">
          <h4 className="text-sm font-semibold text-stone-800">Note interne de clôture</h4>
          <label className="register-form__field">
            <span>Note de clôture</span>
            <textarea
              rows={3}
              value={closureNote}
              onChange={(event) => onClosureNoteChange(event.target.value)}
            />
          </label>
        </div>

        <button
          type="button"
          className="register-form__submit w-full"
          disabled={!finalOffer}
          onClick={onCloseTradeIn}
        >
          Clôturer et générer le justificatif
        </button>

        <div className="flex flex-wrap gap-3 text-sm">
          {selected.ribAvailable && (
            <button type="button" className="underline" onClick={() => onDownloadDocument('rib')}>
              Télécharger le RIB
            </button>
          )}
          {selected.receiptAvailable && (
            <button type="button" className="underline" onClick={() => onDownloadDocument('receipt')}>
              Télécharger le justificatif
            </button>
          )}
        </div>

        {selected.voucherCode && (
          <p className="rounded bg-white p-3 text-sm">
            <strong>Code d’avoir client :</strong>{' '}
            <span className="font-mono">{selected.voucherCode}</span>
          </p>
        )}
      </div>
    </section>
  );
};
