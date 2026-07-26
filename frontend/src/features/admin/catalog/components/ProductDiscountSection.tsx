import type { ChangeEvent } from 'react';

import type { ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductDiscountSection = ({ form, onChange }: { form: ProductFormState; onChange: (event: FormChangeEvent) => void }) => (
  <div className="catalog-form-row">
    <span className="register-form__label">Remise</span>
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
      <label className="booking__checkbox"><input type="checkbox" name="discountEnabled" checked={form.discountEnabled} onChange={onChange} />Activer une remise</label>
      {form.discountEnabled && <>
        <label>Type de remise<select name="discountType" value={form.discountType} onChange={onChange}><option value="percent">Pourcentage (%)</option><option value="fixed">Montant fixe (€)</option></select></label>
        <label>Valeur<input name="discountValue" type="number" step={form.discountType === 'percent' ? '1' : '0.01'} min="0" value={form.discountValue} onChange={onChange} /></label>
        <label>Début (optionnel)<input name="discountStartsAt" type="date" value={form.discountStartsAt} onChange={onChange} /></label>
        <label>Fin (optionnel)<input name="discountEndsAt" type="date" value={form.discountEndsAt} onChange={onChange} /></label>
      </>}
    </div>
  </div>
);
