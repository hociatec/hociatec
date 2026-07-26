import { type ChangeEvent } from 'react';

import { type ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductPublicationSection = ({ form, onChange }: {
  form: ProductFormState;
  onChange: (event: FormChangeEvent) => void;
}) => (
  <div className="catalog-form-row catalog-form-row--columns">
    <label className="booking__checkbox">
      <input type="checkbox" name="isPublished" checked={form.isPublished} onChange={onChange} />
      Produit visible sur le site public
    </label>
    <label className="booking__checkbox">
      <input type="checkbox" name="isFeaturedHome" checked={form.isFeaturedHome} onChange={onChange} />
      Mettre en avant sur la page d'accueil
    </label>
  </div>
);
