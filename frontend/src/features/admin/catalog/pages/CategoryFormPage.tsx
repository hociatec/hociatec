import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  createCategory,
  fetchAdminCategory,
  updateCategory,
  type CatalogCategory,
  type UpsertCategoryPayload,
} from '@/features/catalog/adminApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';
import { slugify } from '@/shared/lib/slugify';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { normalizeAttributeCode } from '@/features/admin/catalog/utils/productFormUtils';
import type { CatalogCategoryAttributeDefinition } from '@/features/catalog/adminApi';

type CategoryFormState = {
  name: string;
  slug: string;
  description: string;
  isVisible: boolean;
  attributeDefinitions: CatalogCategoryAttributeDefinition[];
};

const emptyForm: CategoryFormState = {
  name: '',
  slug: '',
  description: '',
  isVisible: true,
  attributeDefinitions: [],
};

export const CategoryFormPage = () => {
  const { categoryId } = useParams();
  const parsedCategoryId = parseNullablePositiveInteger(categoryId);
  const safeCategoryId = parsedCategoryId ?? 0;
  const isEdit = parsedCategoryId !== null;
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(600);
  const queryClient = useQueryClient();

  useDocumentTitle(isEdit ? 'Admin - Modifier une catégorie' : 'Admin - Nouvelle catégorie');

  const [form, setForm] = useState<CategoryFormState>(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const categoryQuery = useQuery<CatalogCategory, Error>({
    queryKey: adminCatalogQueryKeys.category(isEdit ? parsedCategoryId : null),
    queryFn: () => fetchAdminCategory(safeCategoryId),
    enabled: isEdit,
  });
  const saveMutation = useMutation({
    mutationFn: (payload: UpsertCategoryPayload) =>
      isEdit ? updateCategory(safeCategoryId, payload) : createCategory(payload),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.categories() });
      setMessage(
        response.message ??
          (isEdit ? 'La catégorie a bien été mise à jour.' : 'La catégorie a bien été créée.'),
      );
      if (!isEdit) setForm(emptyForm);
      navigateWithDelay('/admin/catalog/categories');
    },
    onError: (err) =>
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la catégorie.")),
  });

  useEffect(() => {
    if (categoryQuery.data) populateForm(categoryQuery.data);
  }, [categoryQuery.data]);

  const populateForm = (category: CatalogCategory) => {
    setForm({
      name: category.name,
      slug: category.slug,
      description: category.description ?? '',
      isVisible: category.isVisible,
      attributeDefinitions: category.attributeDefinitions ?? [],
    });
  };

  const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value, type } = event.target;

    if (type === 'checkbox') {
      const input = event.target as HTMLInputElement;
      setForm((prev) => ({ ...prev, [name]: input.checked }));
      return;
    }

    if (name === 'name') {
      const generatedSlug = slugify(value);
      setForm((prev) => {
        const shouldSyncSlug = prev.slug.trim() === '' || prev.slug === slugify(prev.name);
        return {
          ...prev,
          name: value,
          slug: shouldSyncSlug ? generatedSlug : prev.slug,
        };
      });
      return;
    }

    if (name === 'slug') {
      setForm((prev) => ({ ...prev, slug: slugify(value) }));
      return;
    }

    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const parsePayload = (): UpsertCategoryPayload => ({
    name: form.name.trim(),
    slug: form.slug.trim() || null,
    description: form.description.trim() || null,
    isVisible: form.isVisible,
    attributeDefinitions: form.attributeDefinitions
      .map((attribute) => ({
        code: normalizeAttributeCode(attribute.code || attribute.label),
        label: attribute.label.trim(),
        inputType: attribute.inputType,
        helpText: attribute.helpText?.trim() || null,
        options: (attribute.options ?? [])
          .map((option) => option.trim())
          .filter((option) => option !== ''),
        isRequired: attribute.isRequired,
        isGlobalFilter: attribute.isGlobalFilter,
      }))
      .filter((attribute) => attribute.code && attribute.label),
  });

  const addAttributeDefinition = () => {
    setForm((previous) => ({
      ...previous,
      attributeDefinitions: [
        ...previous.attributeDefinitions,
        { code: '', label: '', inputType: 'text', helpText: null, options: [], isRequired: false, isGlobalFilter: false },
      ],
    }));
  };

  const updateAttributeDefinition = (
    index: number,
    field: keyof CatalogCategoryAttributeDefinition,
    value: string | boolean | string[] | null,
  ) => {
    setForm((previous) => ({
      ...previous,
      attributeDefinitions: previous.attributeDefinitions.map((attribute, attributeIndex) => {
        if (attributeIndex !== index) {
          return attribute;
        }

        if (field === 'label') {
          const nextLabel = String(value);
          const nextCode =
            attribute.code.trim() === '' || attribute.code === normalizeAttributeCode(attribute.label)
              ? normalizeAttributeCode(nextLabel)
              : attribute.code;

          return { ...attribute, label: nextLabel, code: nextCode };
        }

        if (field === 'code') {
          return { ...attribute, code: normalizeAttributeCode(String(value)) };
        }

        return { ...attribute, [field]: value };
      }),
    }));
  };

  const removeAttributeDefinition = (index: number) => {
    setForm((previous) => ({
      ...previous,
      attributeDefinitions: previous.attributeDefinitions.filter((_, attributeIndex) => attributeIndex !== index),
    }));
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const payload = parsePayload();

    if (!payload.name) {
      setError('Le nom de la catégorie est requis.');
      return;
    }

    setError(null);
    setMessage(null);
    saveMutation.mutate(payload);
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier une catégorie' : 'Nouvelle catégorie'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/catalog/categories')}
        >
          Retour à la liste
        </button>
      }
    >
      {(error || categoryQuery.error) && (
        <FeedbackMessage>
          {error ??
            getHttpErrorMessage(categoryQuery.error, 'Impossible de charger la catégorie.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {categoryQuery.isLoading ? (
        <LoadingState>Chargement de la catégorie...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span className="register-form__label">Nom</span>
            <input
              className="register-form__input"
              name="name"
              value={form.name}
              onChange={handleChange}
              required
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Slug</span>
            <input
              className="register-form__input"
              name="slug"
              value={form.slug}
              onChange={handleChange}
              placeholder="ex: coupe-cheveux"
            />
          </label>

          <label className="register-form__field">
            <span className="register-form__label">Description</span>
            <textarea
              className="register-form__input"
              name="description"
              rows={4}
              value={form.description}
              onChange={handleChange}
            />
          </label>

          <label className="booking__checkbox">
            <input
              type="checkbox"
              name="isVisible"
              checked={form.isVisible}
              onChange={handleChange}
            />
            Catégorie visible
          </label>

          <section className="catalog-form-section">
            <div className="catalog-form-section__header">
              <h2 className="catalog-form-section__title">Attributs de la catégorie</h2>
              <p className="catalog-form-section__description">
                Définissez les caractéristiques attendues pour les produits de cette catégorie.
              </p>
            </div>

            <div className="catalog-attribute-editor">
              {form.attributeDefinitions.length === 0 && (
                <p className="muted">
                  Aucun attribut configuré. Vous pourrez toujours ajouter une caractéristique exceptionnelle sur un produit.
                </p>
              )}
              {form.attributeDefinitions.map((attribute, index) => (
                <div
                  key={`${attribute.code}-${index}`}
                  className="catalog-form-row catalog-form-row--columns"
                >
                  <label>
                    Libellé
                    <input
                      value={attribute.label}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'label', event.target.value)
                      }
                      placeholder="Ex. Stockage, RAM, Couleur"
                    />
                  </label>
                  <label>
                    Code
                    <input
                      value={attribute.code}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'code', event.target.value)
                      }
                      placeholder="stockage"
                    />
                  </label>
                  <label>
                    Type
                    <select
                      value={attribute.inputType}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'inputType', event.target.value)
                      }
                    >
                      <option value="text">Texte libre</option>
                      <option value="number">Nombre</option>
                      <option value="select">Liste fermée</option>
                      <option value="color">Couleur</option>
                      <option value="boolean">Oui / Non</option>
                    </select>
                  </label>
                  <label>
                    Aide de saisie
                    <input
                      value={attribute.helpText ?? ''}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'helpText', event.target.value)
                      }
                      placeholder="Ex. Choisissez une capacité disponible"
                    />
                  </label>
                  {attribute.inputType === 'select' && (
                    <label>
                      Options
                      <input
                        value={(attribute.options ?? []).join(', ')}
                        onChange={(event) =>
                          updateAttributeDefinition(
                            index,
                            'options',
                            event.target.value
                              .split(',')
                              .map((option) => option.trim())
                              .filter((option) => option !== ''),
                          )
                        }
                        placeholder="Ex. 64 Go, 128 Go, 256 Go"
                      />
                    </label>
                  )}
                  <label className="booking__checkbox">
                    <input
                      type="checkbox"
                      checked={attribute.isRequired}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'isRequired', event.target.checked)
                      }
                    />
                    Attribut obligatoire
                  </label>
                  <label className="booking__checkbox">
                    <input
                      type="checkbox"
                      checked={attribute.isGlobalFilter}
                      onChange={(event) =>
                        updateAttributeDefinition(index, 'isGlobalFilter', event.target.checked)
                      }
                    />
                    Filtre global
                  </label>
                  <div className="catalog-form-field-actions">
                    <button
                      type="button"
                      className="catalog-variant-switcher__remove"
                      onClick={() => removeAttributeDefinition(index)}
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              ))}
              <button
                type="button"
                className="catalog-admin-actions__edit w-fit"
                onClick={addAttributeDefinition}
              >
                Ajouter un attribut
              </button>
            </div>
          </section>

          <button className="register-form__submit" type="submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
