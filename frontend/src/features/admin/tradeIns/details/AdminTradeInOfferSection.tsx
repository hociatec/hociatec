import type { TradeInDto, TradeInStatus } from '@/features/tradeIns/publicApi';

type AdminTradeInOfferSectionProps = {
  offer: string;
  note: string;
  pendingStatus: TradeInStatus | null;
  selected: TradeInDto;
  onOfferChange: (value: string) => void;
  onNoteChange: (value: string) => void;
  onStatusChange: (value: TradeInStatus) => void;
  onSaveOffer: () => void;
  onChangeStatus: () => void;
  onDelete: () => void;
};

export const AdminTradeInOfferSection = ({
  offer,
  note,
  pendingStatus,
  selected,
  onOfferChange,
  onNoteChange,
  onStatusChange,
  onSaveOffer,
  onChangeStatus,
  onDelete,
}: AdminTradeInOfferSectionProps) => (
  <section aria-labelledby="trade-in-action-title" className="rounded-lg border border-brand-200 p-4">
    <h3
      id="trade-in-action-title"
      className="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-600"
    >
      Actions administratives
    </h3>
    <div className="space-y-6">
      <div role="group" aria-labelledby="trade-in-offer-group-title" className="space-y-4">
        <h4 id="trade-in-offer-group-title" className="text-sm font-semibold text-stone-800">
          Proposition commerciale
        </h4>
        <label className="register-form__field">
          <span>Offre définitive (€)</span>
          <input
            type="number"
            min="0"
            step="0.01"
            value={offer}
            onChange={(event) => onOfferChange(event.target.value)}
          />
        </label>
        <label className="register-form__field">
          <span>Message client</span>
          <textarea rows={4} value={note} onChange={(event) => onNoteChange(event.target.value)} />
        </label>
      </div>
      <button className="register-form__submit w-full" type="button" disabled={!offer} onClick={onSaveOffer}>
        Enregistrer l’offre
      </button>

      <div
        role="group"
        aria-labelledby="trade-in-status-group-title"
        className="space-y-4 border-t border-brand-100 pt-5"
      >
        <h4 id="trade-in-status-group-title" className="text-sm font-semibold text-stone-800">
          Suivi de la demande
        </h4>
        <fieldset className="space-y-3">
          <legend className="text-sm text-stone-600">Choisissez le prochain statut</legend>
          <div className="grid gap-2 sm:grid-cols-2">
            {selected.allowedNextStatusDetails.map(({ value, label }) => (
              <label
                key={value}
                className="flex cursor-pointer items-center gap-3 rounded border border-brand-100 p-3 hover:bg-brand-50"
              >
                <input
                  type="radio"
                  name="trade-in-status"
                  value={value}
                  checked={pendingStatus === value}
                  onChange={() => onStatusChange(value as TradeInStatus)}
                />
                <span>{label}</span>
              </label>
            ))}
          </div>
        </fieldset>
        <button
          type="button"
          className="register-form__submit w-full"
          disabled={!pendingStatus || pendingStatus === selected.status}
          onClick={onChangeStatus}
        >
          Enregistrer le statut
        </button>
      </div>

      <div role="group" aria-labelledby="trade-in-delete-group-title" className="border-t border-red-100 pt-5">
        <h4 id="trade-in-delete-group-title" className="sr-only">
          Suppression
        </h4>
        <button
          type="button"
          className="w-full rounded border border-red-200 px-4 py-2 font-semibold text-red-700 hover:bg-red-50"
          onClick={onDelete}
        >
          Supprimer définitivement la demande
        </button>
      </div>
    </div>
  </section>
);
