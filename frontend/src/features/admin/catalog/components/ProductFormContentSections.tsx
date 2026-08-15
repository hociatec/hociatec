import { type ChangeEvent } from 'react';

import {
  type CatalogBrand,
  type CatalogCategory,
} from '@/features/catalog/adminApi';
import type { ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { normalizeSearchText } from '@/shared/lib/searchText';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductGeneralSection = ({
  brandQuery,
  categories,
  filteredBrands,
  form,
  onBrandQueryChange,
  onBrandSelection,
  onChange,
}: {
  brandQuery: string;
  categories: CatalogCategory[];
  filteredBrands: CatalogBrand[];
  form: ProductFormState;
  onBrandQueryChange: (value: string) => void;
  onBrandSelection: (brand: CatalogBrand) => void;
  onChange: (event: FormChangeEvent) => void;
}) => (
  <section className="catalog-form-section">
    <div className="catalog-form-section__header">
      <h2 className="catalog-form-section__title">Informations générales</h2>
      <p className="catalog-form-section__description">
        Renseignez les informations communes au produit avant de définir ses variantes.
      </p>
    </div>

    <div className="catalog-form-row catalog-form-row--columns">
      <label>
        Nom du produit
        <input name="name" value={form.name} onChange={onChange} maxLength={180} required />
      </label>
      <label>
        SKU
        <input
          name="sku"
          value={form.sku}
          onChange={onChange}
          maxLength={60}
          placeholder="Identifiant interne"
          required
        />
      </label>
      <label>
        Slug (URL)
        <input
          name="slug"
          value={form.slug}
          onChange={onChange}
          maxLength={200}
          placeholder="ex : solution-supervision"
        />
      </label>
      <label>
        Catégorie
        <select name="categoryId" value={form.categoryId} onChange={onChange} required>
          <option value="">Sélectionnez une catégorie</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </label>
      <fieldset className="catalog-form-fieldset">
        <legend>Modes disponibles</legend>
        <div className="flex flex-wrap gap-4">
          <label>
            <input
              name="availableForSale"
              type="checkbox"
              checked={form.availableForSale}
              onChange={onChange}
            />{' '}
            Vente
          </label>
          <label>
            <input
              name="availableForRental"
              type="checkbox"
              checked={form.availableForRental}
              onChange={onChange}
            />{' '}
            Location
          </label>
        </div>
      </fieldset>
      <label>
        Prix de vente (euros TTC)
        <input
          name="salePrice"
          type="number"
          step="0.01"
          min="0"
          value={form.salePrice}
          onChange={onChange}
          required={form.availableForSale}
          disabled={!form.availableForSale}
        />
        <span className="muted">
          Utilisé pour la variante principale et comme valeur par défaut des variantes supplémentaires.
        </span>
      </label>
      <label>
        Prix mensuel de location (euros TTC / mois)
        <input
          name="rentalPrice"
          type="number"
          step="0.01"
          min="0"
          value={form.rentalPrice}
          onChange={onChange}
          required={form.availableForRental}
          disabled={!form.availableForRental}
        />
        <span className="muted">
          Utilisé pour la variante principale et comme valeur par défaut des variantes supplémentaires.
        </span>
      </label>
      <label>
        Année du modèle
        <input
          name="releaseYear"
          type="number"
          min="2000"
          max="2100"
          step="1"
          value={form.releaseYear}
          onChange={onChange}
          placeholder="2025"
        />
      </label>
    </div>

    <div className="catalog-brand-picker">
      <label htmlFor="product-brand-search">
        Marque
        <input
          id="product-brand-search"
          name="brandSearch"
          value={brandQuery}
          onChange={(event) => onBrandQueryChange(event.target.value)}
          maxLength={80}
          placeholder="Tapez une ou plusieurs lettres"
          required
        />
      </label>
      <div className="catalog-brand-picker__header">
        <span className="muted">
          La marque est obligatoire. Recherchez puis cochez une marque existante.
        </span>
        {form.brand && (
          <span className="catalog-brand-picker__selected">Marque choisie : {form.brand}</span>
        )}
      </div>
      <div className="catalog-brand-picker__results">
        {brandQuery.trim() === '' ? (
          <p className="catalog-brand-picker__empty">
            Saisissez au moins une lettre pour afficher les marques disponibles.
          </p>
        ) : filteredBrands.length === 0 ? (
          <p className="catalog-brand-picker__empty">
            Aucune marque trouvée. Ajoutez-la d’abord dans l’onglet Marques.
          </p>
        ) : (
          filteredBrands.map((brand) => {
            const checked = normalizeSearchText(form.brand) === normalizeSearchText(brand.name);

            return (
              <label
                key={brand.id}
                className={`catalog-brand-picker__option${checked ? ' is-selected' : ''}`}
              >
                <input
                  type="radio"
                  name="brandIdSelection"
                  checked={checked}
                  onChange={() => onBrandSelection(brand)}
                />
                <span>{brand.name}</span>
              </label>
            );
          })
        )}
      </div>
    </div>
  </section>
);
