import { type ChangeEvent } from 'react';

import { type CatalogBrand, type CatalogCategory, type CatalogProduct } from '@/features/catalog/api';
import {
  DEFAULT_COLOR_OPTIONS,
  DEFAULT_STORAGE_OPTIONS,
  GALLERY_SIZE,
  type ProductFormState,
  type VariantRowState,
} from '@/features/admin/catalog/utils/productFormConfig';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

export const ProductFormDatalists = () => (
  <>
    <datalist id="storage-capacities">
      {DEFAULT_STORAGE_OPTIONS.map((option) => (
        <option key={option} value={option} />
      ))}
    </datalist>
    <datalist id="color-options">
      {DEFAULT_COLOR_OPTIONS.map((option) => (
        <option key={option} value={option} />
      ))}
    </datalist>
  </>
);

export const VariantSwitcherSection = ({
  currentProductId,
  deletingVariantId,
  groupVariants,
  formatVariantDetails,
  onDeleteVariant,
  onNavigateVariant,
}: {
  currentProductId: number | null;
  deletingVariantId: number | null;
  groupVariants: CatalogProduct[];
  formatVariantDetails: (product: CatalogProduct) => string;
  onDeleteVariant: (variant: CatalogProduct) => void;
  onNavigateVariant: (variantId: number) => void;
}) => (
  <section className="catalog-form-section catalog-variant-switcher">
    <div className="catalog-form-section__header">
      <h2 className="catalog-form-section__title">Variantes enregistrées</h2>
      <p className="catalog-form-section__description">
        Chaque variante est un produit distinct. Choisissez celle que vous voulez modifier.
      </p>
    </div>

    <div className="catalog-variant-switcher__list">
      {groupVariants.map((variant, index) => {
        const isActive = variant.id === currentProductId;

        return (
          <div key={variant.id} className={`catalog-variant-switcher__card${isActive ? ' is-active' : ''}`}>
            <div className="catalog-variant-switcher__item">
              <span className="catalog-variant-switcher__eyebrow">
                Variante {variant.variantPosition ?? index + 1}
              </span>
              <span className="catalog-variant-switcher__name">{formatVariantDetails(variant)}</span>
              <span className="catalog-variant-switcher__meta">
                SKU : {variant.sku} · Stock : {variant.stock}
              </span>
            </div>
            <button
              type="button"
              className="catalog-variant-switcher__select"
              disabled={isActive}
              onClick={() => onNavigateVariant(variant.id)}
            >
              {isActive ? 'Variante active' : 'Modifier cette variante'}
            </button>
            <button
              type="button"
              className="catalog-variant-switcher__remove"
              disabled={deletingVariantId !== null}
              onClick={() => onDeleteVariant(variant)}
            >
              {deletingVariantId === variant.id ? 'Suppression...' : 'Retirer la variante'}
            </button>
          </div>
        );
      })}
    </div>
  </section>
);

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
        <input name="sku" value={form.sku} onChange={onChange} maxLength={60} placeholder="Identifiant interne" required />
      </label>
      <label>
        Slug (URL)
        <input name="slug" value={form.slug} onChange={onChange} maxLength={200} placeholder="ex : solution-supervision" />
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
        <input name="price" type="number" step="0.01" min="0" value={form.price} onChange={onChange} required />
      </label>
      <label>
        Année du modèle
        <input name="releaseYear" type="number" min="2000" max="2100" step="1" value={form.releaseYear} onChange={onChange} placeholder="2025" />
      </label>
      <label>
        Mémoire RAM (Go)
        <input name="memoryRam" type="number" min="1" max="256" step="1" value={form.memoryRam} onChange={onChange} placeholder="8" />
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
        <span className="muted">La marque est obligatoire. Recherchez puis cochez une marque existante.</span>
        {form.brand && <span className="catalog-brand-picker__selected">Marque choisie : {form.brand}</span>}
      </div>
      <div className="catalog-brand-picker__results">
        {brandQuery.trim() === '' ? (
          <p className="catalog-brand-picker__empty">Saisissez au moins une lettre pour afficher les marques disponibles.</p>
        ) : filteredBrands.length === 0 ? (
          <p className="catalog-brand-picker__empty">Aucune marque trouvée. Ajoutez-la d’abord dans l’onglet Marques.</p>
        ) : (
          filteredBrands.map((brand) => {
            const checked = form.brand.toLowerCase() === brand.name.toLowerCase();

            return (
              <label key={brand.id} className={`catalog-brand-picker__option${checked ? ' is-selected' : ''}`}>
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

export const ProductDiscountSection = ({ form, onChange }: {
  form: ProductFormState;
  onChange: (event: FormChangeEvent) => void;
}) => (
  <div className="catalog-form-row">
    <span className="register-form__label">Remise</span>
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
      <label className="booking__checkbox">
        <input type="checkbox" name="discountEnabled" checked={form.discountEnabled} onChange={onChange} />
        Activer une remise
      </label>

      {form.discountEnabled && (
        <>
          <label>
            Type de remise
            <select name="discountType" value={form.discountType} onChange={onChange}>
              <option value="percent">Pourcentage (%)</option>
              <option value="fixed">Montant fixe (€)</option>
            </select>
          </label>
          <label>
            Valeur
            <input
              name="discountValue"
              type="number"
              step={form.discountType === 'percent' ? '1' : '0.01'}
              min="0"
              value={form.discountValue}
              onChange={onChange}
            />
          </label>
          <label>
            Début (optionnel)
            <input name="discountStartsAt" type="date" value={form.discountStartsAt} onChange={onChange} />
          </label>
          <label>
            Fin (optionnel)
            <input name="discountEndsAt" type="date" value={form.discountEndsAt} onChange={onChange} />
          </label>
        </>
      )}
    </div>
  </div>
);

export const ProductCurrentVariantSection = ({ currentVariantPosition, form, onChange }: {
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
        <select id="product-main-color" name="color" value={form.color} onChange={onChange}>
          <option value="">Sélectionnez une couleur</option>
          {DEFAULT_COLOR_OPTIONS.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </label>
      <label htmlFor="product-main-storage">
        Stockage
        <input id="product-main-storage" name="storageCapacity" type="number" min="1" max="4096" step="1" value={form.storageCapacity} onChange={onChange} placeholder="256" />
      </label>
      <label>
        Stock
        <input name="stock" type="number" min="0" value={form.stock} onChange={onChange} required />
        <span className="muted">Le stock est géré pour chaque variante individuellement.</span>
      </label>
    </div>
  </section>
);

export const ProductExtraVariantsSection = ({ rows, onAdd, onRemove, onUpdate }: {
  rows: VariantRowState[];
  onAdd: () => void;
  onRemove: (index: number) => void;
  onUpdate: (index: number, field: keyof VariantRowState, value: string) => void;
}) => (
  <section className="catalog-form-section">
    <div className="catalog-form-section__header">
      <h2 className="catalog-form-section__title">Variantes supplémentaires</h2>
      <p className="catalog-form-section__description">
        Ajoutez des variantes avec leur couleur, leur stockage et leur stock propre.
      </p>
    </div>

    <div className="catalog-variants-list">
      {rows.map((row, index) => (
        <div key={`${index}-${row.color}-${row.storageCapacity}`} className="catalog-variant-card">
          <div className="catalog-variant-card__header">
            <h3 className="catalog-variant-card__title">Variante {index + 1}</h3>
            <button type="button" className="catalog-variant-switcher__remove" onClick={() => onRemove(index)}>
              Supprimer
            </button>
          </div>

          <div className="catalog-form-row catalog-form-row--columns">
            <label htmlFor={`variant-color-${index}`}>
              Couleur
              <select id={`variant-color-${index}`} value={row.color} onChange={(event) => onUpdate(index, 'color', event.target.value)}>
                <option value="">Sélectionnez une couleur</option>
                {DEFAULT_COLOR_OPTIONS.map((option) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))}
              </select>
            </label>
            <label htmlFor={`variant-storage-${index}`}>
              Stockage
              <input id={`variant-storage-${index}`} type="number" min="1" max="4096" step="1" value={row.storageCapacity} onChange={(event) => onUpdate(index, 'storageCapacity', event.target.value)} placeholder="128" />
            </label>
            <label htmlFor={`variant-stock-${index}`}>
              Stock
              <input id={`variant-stock-${index}`} type="number" min="0" value={row.stock} onChange={(event) => onUpdate(index, 'stock', event.target.value)} />
            </label>
          </div>
        </div>
      ))}

      <button type="button" className="catalog-admin-actions__edit w-fit" onClick={onAdd}>
        Ajouter une variante
      </button>
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
        <textarea name="shortDescription" rows={2} maxLength={240} value={form.shortDescription} onChange={onChange} />
      </label>
    </div>

    <div className="catalog-form-row">
      <label>
        Description détaillée
        <textarea name="description" rows={6} value={form.description} onChange={onChange} required />
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
                  <input type="file" accept="image/*" onChange={(event) => onGalleryFileChange(index, event.target.files)} hidden />
                  {preview ? 'Remplacer' : 'Ajouter'}
                </label>
                {(preview || hasExisting) && (
                  <button type="button" className="catalog-gallery-remove" onClick={() => onRemoveGallery(index)}>
                    {hasNewFile ? 'Annuler' : 'Supprimer'}
                  </button>
                )}
              </div>
              {index === 0 && (
                <p className="muted mt-1.5">
                  Image principale affichée sur les cartes produits.
                </p>
              )}
              {galleryToRemove.includes(index) && (
                <p className="catalog-gallery-alert">L'image sera supprimée lors de l'enregistrement.</p>
              )}
            </div>
          );
        })}
      </div>
    </div>

    <div className="catalog-form-row catalog-form-row--columns">
      <label>
        Texte alternatif (accessibilité)
        <input name="imageAlt" value={form.imageAlt} onChange={onChange} maxLength={160} placeholder="Décrivez brièvement l'image principale" />
      </label>
    </div>
  </>
);

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
