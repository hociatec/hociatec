import type { TradeInInput } from '../../types';

interface TradeInConsentSubmitProps {
  form: TradeInInput;
  saving: boolean;
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInConsentSubmit = ({ form, saving, onChange }: TradeInConsentSubmitProps) => (
  <>
    <label className="flex items-start gap-3 rounded-2xl border border-brand-100 bg-white p-4 text-sm text-stone-700 shadow-sm">
      <input
        type="checkbox"
        checked={form.consent}
        onChange={(event) => onChange('consent', event.target.checked)}
        required
      />
      <span>
        J’accepte que Hociatec utilise ces informations pour étudier ma demande de reprise
        et me recontacter.
      </span>
    </label>
    <button
      className="inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
      type="submit"
      disabled={saving || !form.consent || !form.rib}
    >
      {saving ? 'Envoi en cours…' : 'Obtenir mon estimation'}
    </button>
  </>
);
