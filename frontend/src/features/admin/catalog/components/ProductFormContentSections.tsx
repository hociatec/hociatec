import { type ChangeEvent } from 'react';

import {
  type CatalogBrand,
  type CatalogCategory,
} from '@/features/catalog/api';
import {
  GALLERY_SIZE,
  type ProductFormState,
} from '@/features/admin/catalog/utils/productFormConfig';

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
      <label>
        Type du produit
        <select name="sellingType" value={form.sellingType} onChange={onChange}>
          <option value="sale">Vente</option>
          <option value="rental">Location</option>
        </select>
      </label>
      <label>
        {form.sellingType === 'rental' ? 'Prix mensuel (euros TTC / mois)' : 'Prix (en euros TTC)'}
        <input
          name="price"
          type="number"
          step="0.01"
          min="0"
          value={form.price}
          onChange={onChange}
          required
        />
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
      <label>
        Mémoire RAM (Go)
        <input
          name="memoryRam"
          type="number"
          min="1"
          max="256"
          step="1"
          value={form.memoryRam}
          onChange={onChange}
          placeholder="8"
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
            const checked = form.brand.toLowerCase() === brand.name.toLowerCase();

            return (
              <label
                key={brand.id}
                className={`catalog-brand-picker__option${checked ? ' is-selected' : ''}`}
              >
                <input type="checkbox" checked={checked} onChange={() => onBrandSelection(brand)} />
                <span>{brand.name}</span>
              </label>
            );
          })
        )}
      </div>
    </div>
  </section>
);

export const ProductContentMediaSection = ({
  form,
  galleryFiles,
  galleryPreviews,
  galleryToRemove,
  initialGallery,
  onChange,
  onGalleryFileChange,
  onRemoveGallery,
}: {
  form: ProductFormState;
  galleryFiles: Array<File | null>;
  galleryPreviews: Array<string | null>;
  galleryToRemove: number[];
  initialGallery: Array<string | null>;
  onChange: (event: FormChangeEvent) => void;
  onGalleryFileChange: (index: number, fileList: FileList | null) => void;
  onRemoveGallery: (index: number) => void;
}) => (
  <>
    <div className="catalog-form-row">
      <label>
        Description courte
        <textarea
          name="shortDescription"
          rows={2}
          maxLength={240}
          value={form.shortDescription}
          onChange={onChange}
        />
      </label>
    </div>

    <div className="catalog-form-row">
      <label>
        Description détaillée
        <textarea
          name="description"
          rows={6}
          value={form.description}
          onChange={onChange}
          required
        />
      </label>
    </div>

    <div className="catalog-form-row">
      <span className="register-form__label">Galerie</span>
      <div className="catalog-gallery-grid">
        {Array.from({ length: GALLERY_SIZE }, (_, index) => {
          const preview = galleryPreviews[index];
          const hasExisting = initialGallery[index] !== null;
          const hasNewFile = galleryFiles[index] instanceof File;

          return (
            <div key={index} className="catalog-gallery-slot">
              <div className="catalog-gallery-preview" aria-label={`Image ${index + 1}`}>
                {preview ? (
                  <img src={preview} alt={`Illustration ${index + 1}`} />
                ) : (
                  <div className="catalog-gallery-placeholder">
                    <span>{index + 1}</span>
                  </div>
                )}
              </div>
              <div className="catalog-gallery-slot__actions">
                <label className="catalog-gallery-upload">
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(event) => onGalleryFileChange(index, event.target.files)}
                    hidden
                  />
                  {preview ? 'Remplacer' : 'Ajouter'}
                </label>
                {(preview || hasExisting) && (
                  <button
                    type="button"
                    className="catalog-gallery-remove"
                    onClick={() => onRemoveGallery(index)}
                  >
                    {hasNewFile ? 'Annuler' : 'Supprimer'}
                  </button>
                )}
              </div>
              {index === 0 && (
                <p className="muted mt-1.5">Image principale affichée sur les cartes produits.</p>
              )}
              {galleryToRemove.includes(index) && (
                <p className="catalog-gallery-alert">
                  L'image sera supprimée lors de l'enregistrement.
                </p>
              )}
            </div>
          );
        })}
      </div>
    </div>

    <div className="catalog-form-row catalog-form-row--columns">
      <label>
        Texte alternatif (accessibilité)
        <input
          name="imageAlt"
          value={form.imageAlt}
          onChange={onChange}
          maxLength={160}
          placeholder="Décrivez brièvement l'image principale"
        />
      </label>
    </div>
  </>
);
