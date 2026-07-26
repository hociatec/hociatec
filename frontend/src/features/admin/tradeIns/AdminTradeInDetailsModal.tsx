import type { RefObject } from 'react';

import { formatEuroCents, formatFrenchDate } from '@/shared/lib/formatters';
import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/types';

type PaymentMethod = string;

type AdminTradeInDetailsModalProps = {
  selected: TradeInDto;
  closeButtonRef: RefObject<HTMLButtonElement | null>;
  pendingStatus: TradeInStatus | null;
  offer: string;
  note: string;
  finalOffer: string;
  paymentMethod: PaymentMethod;
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
}: AdminTradeInDetailsModalProps) => (
  <div
    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    role="presentation"
    onMouseDown={(event) => {
      if (event.target === event.currentTarget) onClose();
    }}
  >
    <section
      className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="trade-in-dialog-title"
    >
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1">
          <h2 id="trade-in-dialog-title" className="text-lg font-semibold">{selected.reference}</h2>
          <p>{selected.productName} · {selected.brand ?? 'Marque non renseignée'}</p>
          <span className="mt-3 inline-flex rounded-full bg-brand-100 px-3 py-1 text-sm font-semibold text-brand-900">
            {selected.statusLabel}
          </span>
        </div>
        <button ref={closeButtonRef} type="button" className="rounded border border-brand-200 px-3 py-2" onClick={onClose}>
          Fermer
        </button>
      </div>

      <div className="mt-5 space-y-5">
        <section aria-labelledby="trade-in-contact-title" className="rounded-lg bg-stone-50 p-4">
          <h3 id="trade-in-contact-title" className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600">Coordonnées du demandeur</h3>
          <dl className="grid gap-3 sm:grid-cols-2">
            <InfoItem label="Nom" value={`${selected.contact?.firstName ?? ''} ${selected.contact?.lastName ?? ''}`} />
            <InfoItem label="E-mail" value={selected.contact?.email ?? 'Non renseigné'} />
            <InfoItem label="Téléphone" value={selected.contact?.phone ?? 'Non renseigné'} />
            <InfoItem label="Demande créée le" value={formatFrenchDate(selected.createdAt) ?? 'Date inconnue'} />
          </dl>
        </section>

        <section aria-labelledby="trade-in-equipment-title" className="rounded-lg border border-brand-100 p-4">
          <h3 id="trade-in-equipment-title" className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600">Informations sur le matériel</h3>
          <dl className="grid gap-3 sm:grid-cols-2">
            <InfoItem label="Catégorie" value={selected.categoryLabel} />
            <InfoItem label="Produit / modèle" value={selected.productName} />
            <InfoItem label="Marque" value={selected.brand ?? 'Non renseignée'} />
            <InfoItem label="Prix payé à l’achat" value={formatEuroCents(selected.purchasePriceCents)} />
            <InfoItem label="Année d’achat" value={String(selected.purchaseYear)} />
            <InfoItem label="État déclaré" value={selected.conditionLabel} />
            <InfoItem label="Fonctionnel" value={selected.functional ? 'Oui' : 'Non'} />
            <InfoItem label="Accessoires" value={selected.hasAccessories ? 'Oui' : 'Non'} />
            <InfoItem label="Preuve d’achat" value={selected.hasProofOfPurchase ? 'Oui' : 'Non'} />
          </dl>
          <div className="mt-4 rounded-md bg-brand-50 p-3">
            <p className="text-sm text-stone-600">Description et défauts signalés</p>
            <p className="mt-1 whitespace-pre-wrap">{selected.description}</p>
          </div>
        </section>

        <section aria-labelledby="trade-in-estimate-title" className="rounded-lg bg-emerald-50 p-4">
          <h3 id="trade-in-estimate-title" className="text-sm font-semibold uppercase tracking-wide text-emerald-900">Estimation indicative</h3>
          <p className="mt-2 text-2xl font-bold text-emerald-950">{formatEuroCents(selected.estimatedMinCents)} – {formatEuroCents(selected.estimatedMaxCents)}</p>
          <p className="mt-1 text-sm text-emerald-900">Cette estimation doit être confirmée par l’équipe Hociatec après contrôle.</p>
        </section>

        <section aria-labelledby="trade-in-action-title" className="rounded-lg border border-brand-200 p-4">
          <h3 id="trade-in-action-title" className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600">Actions administratives</h3>
          <div className="space-y-6">
            <div role="group" aria-labelledby="trade-in-offer-group-title" className="space-y-4">
              <h4 id="trade-in-offer-group-title" className="text-sm font-semibold text-stone-800">Proposition commerciale</h4>
              <label className="register-form__field"><span>Offre définitive (€)</span><input type="number" min="0" step="0.01" value={offer} onChange={(event) => onOfferChange(event.target.value)} /></label>
              <label className="register-form__field"><span>Message client</span><textarea rows={4} value={note} onChange={(event) => onNoteChange(event.target.value)} /></label>
            </div>
            <button className="register-form__submit w-full" type="button" disabled={!offer} onClick={onSaveOffer}>Enregistrer l’offre</button>
            <div role="group" aria-labelledby="trade-in-status-group-title" className="space-y-4 border-t border-brand-100 pt-5">
              <h4 id="trade-in-status-group-title" className="text-sm font-semibold text-stone-800">Suivi de la demande</h4>
              <fieldset className="space-y-3">
                <legend className="text-sm text-stone-600">Choisissez le prochain statut</legend>
                <div className="grid gap-2 sm:grid-cols-2">
                  {selected.allowedNextStatusDetails.map(({ value, label }) => (
                    <label key={value} className="flex cursor-pointer items-center gap-3 rounded border border-brand-100 p-3 hover:bg-brand-50">
                      <input type="radio" name="trade-in-status" value={value} checked={pendingStatus === value} onChange={() => onStatusChange(value as TradeInStatus)} />
                      <span>{label}</span>
                    </label>
                  ))}
                </div>
              </fieldset>
              <button type="button" className="register-form__submit w-full" disabled={!pendingStatus || pendingStatus === selected.status} onClick={onChangeStatus}>Enregistrer le statut</button>
            </div>
            <div role="group" aria-labelledby="trade-in-delete-group-title" className="border-t border-red-100 pt-5">
              <h4 id="trade-in-delete-group-title" className="sr-only">Suppression</h4>
              <button type="button" className="w-full rounded border border-red-200 px-4 py-2 font-semibold text-red-700 hover:bg-red-50" onClick={onDelete}>Supprimer définitivement la demande</button>
            </div>
          </div>
        </section>

        {(selected.status === 'inspected' || selected.status === 'completed') && (
          <section aria-labelledby="trade-in-close-title" className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <h3 id="trade-in-close-title" className="mb-3 text-sm font-semibold uppercase tracking-wide text-emerald-900">Clôture de la reprise</h3>
            <div className="space-y-4">
              <label className="register-form__field"><span>Montant final (€)</span><input type="number" min="0.01" step="0.01" value={finalOffer} onChange={(event) => onFinalOfferChange(event.target.value)} /></label>
              <PaymentMethodFields value={paymentMethod} onChange={onPaymentMethodChange} />
              <PaymentStatusFields paymentMethod={paymentMethod} value={paymentStatus} onChange={onPaymentStatusChange} />
              <div className="space-y-4 border-t border-emerald-200 pt-5"><h4 className="text-sm font-semibold text-stone-800">Traçabilité du règlement</h4><label className="register-form__field"><span>Référence de transaction</span><input value={transactionReference} onChange={(event) => onTransactionReferenceChange(event.target.value)} placeholder="Ex. VIR-2026-001" /></label></div>
              <div className="space-y-4 border-t border-emerald-200 pt-5"><h4 className="text-sm font-semibold text-stone-800">Note interne de clôture</h4><label className="register-form__field"><span>Note de clôture</span><textarea rows={3} value={closureNote} onChange={(event) => onClosureNoteChange(event.target.value)} /></label></div>
              <button type="button" className="register-form__submit w-full" disabled={!finalOffer} onClick={onCloseTradeIn}>Clôturer et générer le justificatif</button>
              <div className="flex flex-wrap gap-3 text-sm">
                {selected.ribAvailable && <button type="button" className="underline" onClick={() => onDownloadDocument('rib')}>Télécharger le RIB</button>}
                {selected.receiptAvailable && <button type="button" className="underline" onClick={() => onDownloadDocument('receipt')}>Télécharger le justificatif</button>}
              </div>
              {selected.voucherCode && <p className="rounded bg-white p-3 text-sm"><strong>Code d’avoir client :</strong> <span className="font-mono">{selected.voucherCode}</span></p>}
            </div>
          </section>
        )}
      </div>
    </section>
  </div>
);

const PaymentMethodFields = ({ value, onChange }: { value: string; onChange: (value: string) => void }) => (
  <fieldset className="space-y-2">
    <legend className="text-sm font-semibold text-stone-700">Mode de règlement</legend>
    <div className="grid gap-2 sm:grid-cols-2">
      {[['bank_transfer', 'Virement bancaire'], ['cash', 'Espèces'], ['store_credit', 'Avoir client'], ['other', 'Autre']].map(([method, label]) => (
        <label key={method} className="flex items-center gap-2"><input type="radio" name="trade-in-payment-method" value={method} checked={value === method} onChange={() => onChange(method)} /><span>{label}</span></label>
      ))}
    </div>
  </fieldset>
);

const PaymentStatusFields = ({ paymentMethod, value, onChange }: { paymentMethod: string; value: string; onChange: (value: string) => void }) => (
  <fieldset className="space-y-2">
    <legend className="text-sm font-semibold text-stone-700">État du règlement</legend>
    <div className="grid gap-2 sm:grid-cols-2">
      {[['pending', 'Paiement en attente'], ['paid', 'Paiement effectué']].map(([status, label]) => (
        <label key={status} className="flex items-center gap-2"><input type="radio" name="trade-in-payment-status" value={status} checked={value === status} disabled={paymentMethod === 'store_credit' && status === 'pending'} onChange={() => onChange(status)} /><span>{label}</span></label>
      ))}
    </div>
    {paymentMethod === 'store_credit' && <p className="text-sm text-emerald-800">Un avoir client est considéré comme remis dès sa création.</p>}
  </fieldset>
);

const InfoItem = ({ label, value }: { label: string; value: string }) => (
  <div>
    <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">{label}</dt>
    <dd className="mt-1 break-words font-medium text-stone-900">{value}</dd>
  </div>
);
