import type { Dispatch, SetStateAction } from 'react';

import { formatQuotePrice } from '@/features/quotes/utils/quoteFormUtils';
import type { AdminQuoteFormState } from './AdminQuoteFormSections';

type Props = {
  quote: AdminQuoteFormState;
  setQuote: Dispatch<SetStateAction<AdminQuoteFormState | null>>;
  total: { ht: number; vat: number; ttc: number };
};

export const AdminQuoteCustomerFields = ({ quote, setQuote }: Props) => (
  <section>
    <h3 className="mb-2 font-semibold">Client</h3>
    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
      <input placeholder="Nom" value={quote.customer?.name ?? ''} onChange={(event) => setQuote({ ...quote, customer: { ...quote.customer, name: event.target.value } })} />
      <input placeholder="Email" value={quote.customer?.email ?? ''} onChange={(event) => setQuote({ ...quote, customer: { ...quote.customer, email: event.target.value } })} />
      <input placeholder="Entreprise" value={quote.customer?.company ?? ''} onChange={(event) => setQuote({ ...quote, customer: { ...quote.customer, company: event.target.value } })} />
      <input placeholder="Adresse" value={quote.customer?.address ?? ''} onChange={(event) => setQuote({ ...quote, customer: { ...quote.customer, address: event.target.value } })} />
    </div>
  </section>
);

export const AdminQuoteSettingsSummary = ({ quote, setQuote, total }: Props) => (
  <>
    <section>
      <h3 className="mb-2 font-semibold">Paramètres</h3>
      <div className="space-y-2">
        <label className="flex items-center gap-2">Statut<select value={quote.status} onChange={(event) => setQuote({ ...quote, status: event.target.value as AdminQuoteFormState['status'] })}><option value="draft">Brouillon</option><option value="sent">Envoyé</option><option value="accepted">Accepté</option><option value="refused">Refusé</option><option value="expired">Expiré</option></select></label>
        <label className="flex items-center gap-2">Remise globale<input type="number" min={0} step="0.01" value={((quote.discountCents ?? 0) / 100).toFixed(2)} onChange={(event) => setQuote({ ...quote, discountCents: Math.max(0, Math.round(Number(event.target.value.replace(',', '.')) * 100)) })} /></label>
        <label className="flex items-center gap-2">Frais de port<input type="number" min={0} step="0.01" value={((quote.shippingCents ?? 0) / 100).toFixed(2)} onChange={(event) => setQuote({ ...quote, shippingCents: Math.max(0, Math.round(Number(event.target.value.replace(',', '.')) * 100)) })} /></label>
        <label className="flex items-center gap-2">Début de validité<input type="date" value={quote.validFrom ?? ''} onChange={(event) => setQuote({ ...quote, validFrom: event.target.value })} /></label>
        <label className="flex items-center gap-2">Fin de validité<input type="date" value={quote.validUntil ?? ''} onChange={(event) => setQuote({ ...quote, validUntil: event.target.value })} /></label>
        <label className="flex flex-col gap-2">Conditions<textarea rows={7} value={quote.conditions ?? ''} onChange={(event) => setQuote({ ...quote, conditions: event.target.value })} /></label>
      </div>
    </section>
    <section>
      <h3 className="mb-2 font-semibold">Total</h3>
      <div className="space-y-1"><div className="flex justify-between"><span>Total HT</span><strong>{formatQuotePrice(total.ht)}</strong></div><div className="flex justify-between"><span>TVA</span><strong>{formatQuotePrice(total.vat)}</strong></div><div className="flex justify-between"><span>TTC</span><strong>{formatQuotePrice(total.ttc)}</strong></div></div>
    </section>
  </>
);
