import type { Dispatch, SetStateAction } from 'react';

import type { QuoteDraft } from '@/features/quotes/hooks/useCreateQuote';

type Props = {
  form: QuoteDraft;
  setForm: Dispatch<SetStateAction<QuoteDraft>>;
  authenticated: boolean;
};

export const QuoteCustomerFields = ({ form, setForm, authenticated }: Props) => {
  const updateCustomer = (field: 'name' | 'email' | 'company' | 'address', value: string) => {
    setForm((current) => ({ ...current, customer: { ...current.customer, [field]: value } }));
  };

  return (
    <section>
      <h3 className="mb-2 font-semibold">Coordonnées du demandeur</h3>
      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        <label className="register-form__field"><span>Nom complet</span><input placeholder="Ex. Camille Martin" value={form.customer?.name ?? ''} onChange={(event) => updateCustomer('name', event.target.value)} disabled={authenticated} /></label>
        <label className="register-form__field"><span>Email de contact</span><input placeholder="Ex. camille@entreprise.fr" value={form.customer?.email ?? ''} onChange={(event) => updateCustomer('email', event.target.value)} disabled={authenticated} /></label>
        <label className="register-form__field"><span>Entreprise <span className="text-stone-500">(facultatif)</span></span><input placeholder="Ex. Hociatec" value={form.customer?.company ?? ''} onChange={(event) => updateCustomer('company', event.target.value)} /></label>
        <label className="register-form__field"><span>Adresse de facturation <span className="text-stone-500">(facultatif)</span></span><input placeholder="Rue, code postal et ville" value={form.customer?.address ?? ''} onChange={(event) => updateCustomer('address', event.target.value)} disabled={authenticated} /></label>
      </div>
    </section>
  );
};
