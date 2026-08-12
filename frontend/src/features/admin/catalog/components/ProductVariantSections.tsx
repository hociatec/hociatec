import { type ChangeEvent } from 'react';

import { type ProductFormState, type VariantRowState } from '@/features/admin/catalog/utils/productFormConfig';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductCurrentVariantSection = ({
  currentVariantPosition,
  form,
  onChange,
}: {
  currentVariantPosition: number;
  form: ProductFormState;
  onChange: (event: FormChangeEvent) => void;
}) => (
  <section className="catalog-form-section">
    <div className="catalog-form-section__header">
      <h2 className="catalog-form-section__title">Variante {currentVariantPosition}</h2>
      <p className="catalog-form-section__description">
        Définissez la variante affichée avec sa couleur, son stockage et son stock.
      </p>
    </div>

    <div className="catalog-form-row catalog-form-row--columns">
      <label htmlFor="product-main-color">
        Couleur
        <input id="product-main-color" name="color" value={form.color} onChange={onChange} placeholder="Couleur" />
      </label>
      <label htmlFor="product-main-storage">
        Stockage
        <input
          id="product-main-storage"
          name="storageCapacity"
          type="number"
          min="1"
          max="4096"
          step="1"
          value={form.storageCapacity}
          onChange={onChange}
          placeholder="256"
        />
      </label>
      <label>
        Stock
        <input name="stock" type="number" min="0" value={form.stock} onChange={onChange} required />
        <span className="muted">Le stock est géré pour chaque variante individuellement.</span>
      </label>
    </div>
  </section>
);

export const ProductExtraVariantsSection = ({
  rows,
  onAdd,
  onRemove,
  onUpdate,
}: {
  rows: VariantRowState[];
  onAdd: () => void;
  onRemove: (index: number) => void;
  onUpdate: (index: number, field: keyof VariantRowState, value: string) => void;
}) => {
  return (
    <section className="catalog-form-section">
      <div className="catalog-form-section__header">
        <h2 className="catalog-form-section__title">Variantes supplémentaires</h2>
        <p className="catalog-form-section__description">
          Ajoutez des variantes avec leur couleur, leur stockage, leur stock et leur prix propre.
        </p>
      </div>

      <div className="catalog-variants-list">
        {rows.map((row, index) => {
          return (
            <div key={`${index}-${row.color}-${row.storageCapacity}`} className="catalog-variant-card">
              <div className="catalog-variant-card__header">
                <h3 className="catalog-variant-card__title">Variante {index + 1}</h3>
                <button
                  type="button"
                  className="catalog-variant-switcher__remove"
                  onClick={() => onRemove(index)}
                >
                  Supprimer
                </button>
              </div>

              <div className="catalog-form-row catalog-form-row--columns">
                <label htmlFor={`variant-color-${index}`}>
                  Couleur
                  <input
                    id={`variant-color-${index}`}
                    value={row.color}
                    onChange={(event) => onUpdate(index, 'color', event.target.value)}
                    placeholder="Couleur"
                  />
                </label>
                <label htmlFor={`variant-storage-${index}`}>
                  Stockage
                  <input
                    id={`variant-storage-${index}`}
                    type="number"
                    min="1"
                    max="4096"
                    step="1"
                    value={row.storageCapacity}
                    onChange={(event) => onUpdate(index, 'storageCapacity', event.target.value)}
                    placeholder="128"
                  />
                </label>
                <label htmlFor={`variant-stock-${index}`}>
                  Stock
                  <input
                    id={`variant-stock-${index}`}
                    type="number"
                    min="0"
                    value={row.stock}
                    onChange={(event) => onUpdate(index, 'stock', event.target.value)}
                  />
                </label>
                <label htmlFor={`variant-price-${index}`}>
                  Prix
                  <input
                    id={`variant-price-${index}`}
                    type="number"
                    min="0"
                    step="0.01"
                    value={row.price}
                    onChange={(event) => onUpdate(index, 'price', event.target.value)}
                    placeholder="699.00"
                  />
                </label>
              </div>
            </div>
          );
        })}

        <button type="button" className="catalog-admin-actions__edit w-fit" onClick={onAdd}>
          Ajouter une variante
        </button>
      </div>
    </section>
  );
};
