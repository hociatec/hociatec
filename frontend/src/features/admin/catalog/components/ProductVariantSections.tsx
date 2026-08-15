import { type ChangeEvent } from 'react';

import {
  type AttributeRowState,
  type ProductFormState,
  type VariantRowState,
} from '@/features/admin/catalog/utils/productFormConfig';
import type { CatalogCategoryAttributeDefinition } from '@/features/catalog/adminApi';
import { normalizeAttributeCode } from '@/features/admin/catalog/utils/productFormUtils';

type FormChangeEvent = ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>;

type AttributeEditorProps = {
  attributes: AttributeRowState[];
  categoryDefinitions?: CatalogCategoryAttributeDefinition[];
  onAdd: () => void;
  onRemove: (index: number) => void;
  onUpdate: (index: number, field: keyof AttributeRowState, value: string) => void;
  emptyMessage: string;
};

const AttributeEditor = ({
  attributes,
  categoryDefinitions = [],
  onAdd,
  onRemove,
  onUpdate,
  emptyMessage,
}: AttributeEditorProps) => {
  const definitionsByCode = new Map(
    categoryDefinitions.map((definition) => [definition.code, definition] as const),
  );

  return (
    <div className="catalog-attribute-editor">
      {attributes.length === 0 && <p className="muted">{emptyMessage}</p>}
      {attributes.map((attribute, index) => {
        const definition = definitionsByCode.get(attribute.code);
        const inputType = definition?.inputType ?? 'text';
        const helpText = definition?.helpText?.trim() || null;

        return (
          <div key={`${attribute.code}-${index}`} className="catalog-form-row catalog-form-row--columns">
            <label htmlFor={`attribute-label-${index}`}>
              Libellé
              <input
                id={`attribute-label-${index}`}
                value={attribute.label}
                onChange={(event) => {
                  const nextLabel = event.target.value;
                  onUpdate(index, 'label', nextLabel);
                  if (attribute.code.trim() === '' || attribute.code === normalizeAttributeCode(attribute.label)) {
                    onUpdate(index, 'code', normalizeAttributeCode(nextLabel));
                  }
                }}
                placeholder="Ex. Couleur, Matière, RAM"
              />
            </label>
            <label htmlFor={`attribute-code-${index}`}>
              Code
              <input
                id={`attribute-code-${index}`}
                value={attribute.code}
                onChange={(event) => onUpdate(index, 'code', normalizeAttributeCode(event.target.value))}
                placeholder="couleur"
              />
            </label>
            <label htmlFor={`attribute-value-${index}`}>
              Valeur
              {inputType === 'select' ? (
                <select
                  id={`attribute-value-${index}`}
                  value={attribute.value}
                  onChange={(event) => onUpdate(index, 'value', event.target.value)}
                >
                  <option value="">Sélectionnez une valeur</option>
                  {(definition?.options ?? []).map((option) => (
                    <option key={option} value={option}>
                      {option}
                    </option>
                  ))}
                </select>
              ) : inputType === 'boolean' ? (
                <select
                  id={`attribute-value-${index}`}
                  value={attribute.value}
                  onChange={(event) => onUpdate(index, 'value', event.target.value)}
                >
                  <option value="">Sélectionnez</option>
                  <option value="Oui">Oui</option>
                  <option value="Non">Non</option>
                </select>
              ) : (
                <input
                  id={`attribute-value-${index}`}
                  type={inputType === 'number' ? 'number' : inputType === 'color' ? 'color' : 'text'}
                  value={inputType === 'color' && attribute.value === '' ? '#000000' : attribute.value}
                  onChange={(event) => onUpdate(index, 'value', event.target.value)}
                  placeholder="Ex. Bleu, 256 Go, Aluminium"
                />
              )}
              {helpText && <span className="muted">{helpText}</span>}
            </label>
            <div className="catalog-form-field-actions">
              <button
                type="button"
                className="catalog-variant-switcher__remove"
                onClick={() => onRemove(index)}
              >
                Supprimer
              </button>
            </div>
          </div>
        );
      })}
      <button type="button" className="catalog-admin-actions__edit w-fit" onClick={onAdd}>
        Ajouter un attribut
      </button>
    </div>
  );
};

export const ProductCurrentVariantSection = ({
  currentVariantPosition,
  form,
  onChange,
  onAddAttribute,
  onRemoveAttribute,
  onUpdateAttribute,
  categoryDefinitions,
}: {
  currentVariantPosition: number;
  form: ProductFormState;
  onChange: (event: FormChangeEvent) => void;
  onAddAttribute: () => void;
  onRemoveAttribute: (index: number) => void;
  onUpdateAttribute: (index: number, field: keyof AttributeRowState, value: string) => void;
  categoryDefinitions: CatalogCategoryAttributeDefinition[];
}) => (
  <section className="catalog-form-section">
    <div className="catalog-form-section__header">
      <h2 className="catalog-form-section__title">Variante {currentVariantPosition}</h2>
      <p className="catalog-form-section__description">
        Définissez la variante principale affichée avec ses attributs libres et son stock.
      </p>
      {categoryDefinitions.length > 0 && (
        <p className="muted">
          Attributs de catégorie attendus :{' '}
          {categoryDefinitions.map((definition) => definition.label).join(', ')}.
        </p>
      )}
    </div>

    <div className="catalog-form-row catalog-form-row--columns">
      <label>
        Stock
        <input name="stock" type="number" min="0" value={form.stock} onChange={onChange} required />
        <span className="muted">Le stock est géré pour chaque variante individuellement.</span>
      </label>
    </div>

    <AttributeEditor
      attributes={form.attributes}
      categoryDefinitions={categoryDefinitions}
      onAdd={onAddAttribute}
      onRemove={onRemoveAttribute}
      onUpdate={onUpdateAttribute}
      emptyMessage="Ajoutez les attributs qui décrivent cette variante principale."
    />
  </section>
);

export const ProductExtraVariantsSection = ({
  rows,
  form,
  currentVariantPosition,
  onAdd,
  onRemove,
  onUpdate,
  onAddAttribute,
  onRemoveAttribute,
  onUpdateAttribute,
  categoryDefinitions,
}: {
  rows: VariantRowState[];
  form: ProductFormState;
  currentVariantPosition: number;
  onAdd: () => void;
  onRemove: (index: number) => void;
  onUpdate: (index: number, field: keyof VariantRowState, value: string) => void;
  onAddAttribute: (index: number) => void;
  onRemoveAttribute: (rowIndex: number, attributeIndex: number) => void;
  onUpdateAttribute: (
    rowIndex: number,
    attributeIndex: number,
    field: keyof AttributeRowState,
    value: string,
  ) => void;
  categoryDefinitions: CatalogCategoryAttributeDefinition[];
}) => {
  return (
    <section className="catalog-form-section">
      <div className="catalog-form-section__header">
        <h2 className="catalog-form-section__title">Variantes supplémentaires</h2>
        <p className="catalog-form-section__description">
          Ajoutez des variantes avec leurs attributs, leur stock et leurs prix propres.
        </p>
        <p className="muted">
          Si un prix de variante est laissé vide, le prix principal du produit sera repris pour ce mode.
        </p>
        {categoryDefinitions.length > 0 && (
          <p className="muted">
            Les variantes reprennent automatiquement les attributs de la catégorie. Vous pouvez ajouter des attributs supplémentaires si nécessaire.
          </p>
        )}
      </div>

      <div className="catalog-variants-list">
        {rows.map((row, index) => {
          const variantPosition = currentVariantPosition + index + 1;

          return (
            <div key={`${index}-${row.attributes.map((attribute) => attribute.code).join('-')}`} className="catalog-variant-card">
              <div className="catalog-variant-card__header">
                <h3 className="catalog-variant-card__title">Variante {variantPosition}</h3>
                <button
                  type="button"
                  className="catalog-variant-switcher__remove"
                  onClick={() => onRemove(index)}
                >
                  Supprimer
                </button>
              </div>

              <div className="catalog-form-row catalog-form-row--columns">
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
                <label htmlFor={`variant-sale-price-${index}`}>
                  Prix vente
                  <input
                    id={`variant-sale-price-${index}`}
                    type="number"
                    min="0"
                    step="0.01"
                    value={row.salePrice}
                    onChange={(event) => onUpdate(index, 'salePrice', event.target.value)}
                    placeholder="699.00"
                    disabled={!form.availableForSale}
                  />
                </label>
                <label htmlFor={`variant-rental-price-${index}`}>
                  Prix location / mois
                  <input
                    id={`variant-rental-price-${index}`}
                    type="number"
                    min="0"
                    step="0.01"
                    value={row.rentalPrice}
                    onChange={(event) => onUpdate(index, 'rentalPrice', event.target.value)}
                    placeholder="49.00"
                    disabled={!form.availableForRental}
                  />
                </label>
              </div>

              <AttributeEditor
                attributes={row.attributes}
                categoryDefinitions={categoryDefinitions}
                onAdd={() => onAddAttribute(index)}
                onRemove={(attributeIndex) => onRemoveAttribute(index, attributeIndex)}
                onUpdate={(attributeIndex, field, value) =>
                  onUpdateAttribute(index, attributeIndex, field, value)
                }
                emptyMessage="Ajoutez les attributs qui distinguent cette variante."
              />
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
