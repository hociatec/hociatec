import { type ChangeEvent, type FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createProduct,
  fetchAdminCategories,
  fetchAdminProduct,
  updateProduct,
  type CatalogCategory,
  type CatalogProduct,
  type UpsertProductPayload,
} from '@/features/catalog/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import '@/features/catalog/pages/CatalogPages.css';

type ProductFormState = {
  name: string;
  slug: string;
  sku: string;
  price: string;
  sellingType: 'sale' | 'rental';
  brand: string;
  variantGroup: string;
  releaseYear: string;
  storageCapacity: string;
  memoryRam: string;
  color: string;
  stock: string;
  shortDescription: string;
  description: string;
  categoryId: string;
  isPublished: boolean;
  isFeaturedHome: boolean;
  imageAlt: string;
  discountEnabled: boolean;
  discountType: 'percent' | 'fixed';
  discountValue: string; // string input; parsed before submit
  discountStartsAt: string; // yyyy-mm-dd
  discountEndsAt: string; // yyyy-mm-dd
};

type VariantRowState = {
  color: string;
  storageCapacity: string;
  stock: string;
};

const emptyForm: ProductFormState = {
  name: '',
  slug: '',
  sku: '',
  price: '0',
  sellingType: 'sale',
  brand: '',
  variantGroup: '',
  releaseYear: '',
  storageCapacity: '',
  memoryRam: '',
  color: '',
  stock: '0',
  shortDescription: '',
  description: '',
  categoryId: '',
  isPublished: true,
  isFeaturedHome: false,
  imageAlt: '',
  discountEnabled: false,
  discountType: 'percent',
  discountValue: '',
  discountStartsAt: '',
  discountEndsAt: '',
};

const GALLERY_SIZE = 4;
const DEFAULT_STORAGE_OPTIONS = ['64 Go', '128 Go', '256 Go', '512 Go', '1 To', '2 To'];
const DEFAULT_COLOR_OPTIONS = [
  'Noir',
  'Blanc',
  'Bleu',
  'Bleu ciel',
  'Bleu outremer',
  'Vert',
  'Vert nuit',
  'Rose',
  'Rouge',
  'Violet',
  'Violet intense',
  'Titane naturel',
  'Graphite',
  'Or',
];

const slugify = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

export const ProductFormPage = () => {
  const { productId } = useParams();
  const isEdit = useMemo(() => Boolean(productId), [productId]);
  const navigate = useNavigate();

  useDocumentTitle(isEdit ? 'Admin - Modifier un produit' : 'Admin - Nouveau produit');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();

  const [form, setForm] = useState<ProductFormState>(emptyForm);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [saving, setSaving] = useState(false);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [variantRows, setVariantRows] = useState<VariantRowState[]>([]);

  const [galleryFiles, setGalleryFiles] = useState<Array<File | null>>(
    Array.from({ length: GALLERY_SIZE }, () => null),
  );
  const [galleryPreviews, setGalleryPreviews] = useState<Array<string | null>>(
    Array.from({ length: GALLERY_SIZE }, () => null),
  );
  const [initialGallery, setInitialGallery] = useState<Array<string | null>>(
    Array.from({ length: GALLERY_SIZE }, () => null),
  );
  const [galleryToRemove, setGalleryToRemove] = useState<number[]>([]);

  const galleryObjectUrlsRef = useRef<Array<string | null>>(
    Array.from({ length: GALLERY_SIZE }, () => null),
  );

  useEffect(
    () => () => {
      galleryObjectUrlsRef.current.forEach((url, index) => {
        if (url) {
          URL.revokeObjectURL(url);
          galleryObjectUrlsRef.current[index] = null;
        }
      });
    },
    [],
  );

  useEffect(() => {
    if (!isAdmin) {
      return;
    }

    setInitialLoading(true);
    setError(null);

    const load = async () => {
      try {
        const [categoryList, product] = await Promise.all([
          fetchAdminCategories(),
          isEdit ? fetchAdminProduct(Number(productId)) : Promise.resolve(null),
        ]);

        setCategories(categoryList);

        if (product) {
          hydrateFromProduct(product);
        } else if (categoryList.length > 0) {
          setForm((prev) => ({ ...prev, categoryId: categoryList[0].id.toString() }));
        }
      } catch (err: any) {
        setError(err?.message ?? 'Impossible de charger les données du produit.');
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isAdmin, isEdit, productId]);

  const hydrateFromProduct = (product: CatalogProduct) => {
    setForm({
      name: product.name,
      slug: product.slug,
      sku: product.sku,
      price: (product.priceCents / 100).toString(),
      sellingType: product.sellingType,
      brand: product.brand ?? '',
      variantGroup: product.variantGroup ?? '',
      releaseYear: product.releaseYear?.toString() ?? '',
      storageCapacity: product.storageCapacity ?? '',
      memoryRam: product.memoryRam ?? '',
      color: product.color ?? '',
      stock: product.stock.toString(),
      shortDescription: product.shortDescription ?? '',
      description: product.description,
      categoryId: product.category.id.toString(),
      isPublished: product.isPublished,
      isFeaturedHome: product.isFeaturedHome,
      imageAlt: product.imageAlt ?? '',
      discountEnabled: Boolean(product.discount?.type && product.discount?.value !== undefined),
      discountType: (product.discount?.type === 'fixed_cents' ? 'fixed' : 'percent'),
      discountValue:
        product.discount?.type === 'fixed_cents'
          ? ((product.discount?.value ?? 0) / 100).toString()
          : (product.discount?.value ?? '').toString(),
      discountStartsAt: product.discount?.startsAt ? product.discount.startsAt.substring(0, 10) : '',
      discountEndsAt: product.discount?.endsAt ? product.discount.endsAt.substring(0, 10) : '',
    });

    const populatedGallery = Array.from({ length: GALLERY_SIZE }, () => null as string | null);
    product.gallery.forEach((item) => {
      if (item.position >= 0 && item.position < GALLERY_SIZE) {
        populatedGallery[item.position] = item.url;
      }
    });

    setInitialGallery(populatedGallery);
    setGalleryPreviews(populatedGallery);
    setGalleryFiles(Array.from({ length: GALLERY_SIZE }, () => null));
    setGalleryToRemove([]);
    setVariantRows([]);
  };

  const resetGalleryState = () => {
    galleryObjectUrlsRef.current.forEach((url, index) => {
      if (url) {
        URL.revokeObjectURL(url);
        galleryObjectUrlsRef.current[index] = null;
      }
    });

    setGalleryFiles(Array.from({ length: GALLERY_SIZE }, () => null));
    setGalleryPreviews(Array.from({ length: GALLERY_SIZE }, () => null));
    setInitialGallery(Array.from({ length: GALLERY_SIZE }, () => null));
    setGalleryToRemove([]);
  };

  const handleFieldChange = (name: keyof ProductFormState, value: string) => {
    setForm((prev) => {
      if (name === 'name') {
        const generatedSlug = slugify(value);
        const shouldSyncSlug = prev.slug.trim() === '' || prev.slug === slugify(prev.name);
        return {
          ...prev,
          name: value,
          slug: shouldSyncSlug ? generatedSlug : prev.slug,
        };
      }

      if (name === 'slug') {
        return {
          ...prev,
          slug: slugify(value),
        };
      }

      return {
        ...prev,
        [name]: value,
      };
    });
  };

  const handleChange = (
    event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>,
  ) => {
    const { name, value, type, checked } = event.target as HTMLInputElement;

    if (type === 'checkbox') {
      setForm((prev) => ({ ...prev, [name]: checked }));
      return;
    }

    handleFieldChange(name as keyof ProductFormState, value);
  };

  const handleGalleryFileChange = (index: number, fileList: FileList | null) => {
    const file = fileList?.[0] ?? null;

    setGalleryFiles((prev) => {
      const next = [...prev];

      if (galleryObjectUrlsRef.current[index]) {
        URL.revokeObjectURL(galleryObjectUrlsRef.current[index]!);
        galleryObjectUrlsRef.current[index] = null;
      }

      if (file) {
        next[index] = file;
        const objectUrl = URL.createObjectURL(file);
        galleryObjectUrlsRef.current[index] = objectUrl;
        setGalleryPreviews((previous) => {
          const updated = [...previous];
          updated[index] = objectUrl;
          return updated;
        });
        setGalleryToRemove((previous) => previous.filter((value) => value !== index));
      } else {
        next[index] = null;
        const fallback = initialGallery[index];
        setGalleryPreviews((previous) => {
          const updated = [...previous];
          updated[index] = fallback;
          return updated;
        });

        if (!fallback) {
          setGalleryToRemove((previous) => previous.filter((value) => value !== index));
        }
      }

      return next;
    });
  };

  const handleRemoveGallery = (index: number) => {
    setGalleryFiles((prev) => {
      const next = [...prev];
      const objectUrl = galleryObjectUrlsRef.current[index];
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        galleryObjectUrlsRef.current[index] = null;
      }

      next[index] = null;
      setGalleryPreviews((previous) => {
        const updated = [...previous];
        updated[index] = null;
        return updated;
      });

      if (initialGallery[index]) {
        setGalleryToRemove((previous) => Array.from(new Set([...previous, index])));
      } else {
        setGalleryToRemove((previous) => previous.filter((value) => value !== index));
      }

      return next;
    });
  };

  const addVariantRow = () => {
    setVariantRows((previous) => [...previous, { color: '', storageCapacity: '', stock: '0' }]);
  };

  const updateVariantRow = (index: number, field: keyof VariantRowState, value: string) => {
    setVariantRows((previous) =>
      previous.map((row, rowIndex) => (rowIndex === index ? { ...row, [field]: value } : row)),
    );
  };

  const removeVariantRow = (index: number) => {
    setVariantRows((previous) => previous.filter((_, rowIndex) => rowIndex !== index));
  };

  const parsePrice = (value: string) => {
    const normalized = value.replace(',', '.');
    const parsed = Number(normalized);
    return Number.isNaN(parsed) ? -1 : parsed;
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (saving) return;

    const priceValue = parsePrice(form.price);
    const stockValue = Number.parseInt(form.stock, 10);
    const releaseYearValue =
      form.releaseYear.trim() === '' ? null : Number.parseInt(form.releaseYear.trim(), 10);
    const categoryId = Number.parseInt(form.categoryId, 10);
    const variantPayload = variantRows
      .map((row) => {
        const stock = Number.parseInt(row.stock, 10);

        return {
          color: row.color.trim() || null,
          storageCapacity: row.storageCapacity.trim() || null,
          stock,
        };
      })
      .filter((row) => row.color !== null || row.storageCapacity !== null);

    if (Number.isNaN(priceValue) || priceValue < 0) {
      setError('Le prix indiqué est invalide.');
      return;
    }

    if (Number.isNaN(stockValue) || stockValue < 0) {
      setError('Le stock doit être un entier positif.');
      return;
    }

    if (releaseYearValue !== null && (Number.isNaN(releaseYearValue) || releaseYearValue < 2000 || releaseYearValue > 2100)) {
      setError('L’année du modèle doit être comprise entre 2000 et 2100.');
      return;
    }

    if (Number.isNaN(categoryId)) {
      setError('Merci de sélectionner une catégorie.');
      return;
    }

    if (variantPayload.some((row) => Number.isNaN(row.stock) || row.stock < 0)) {
      setError('Le stock des variantes doit être un entier positif.');
      return;
    }

    const galleryPayload = galleryFiles.some(Boolean) ? galleryFiles : undefined;
    const removeGalleryPayload =
      galleryToRemove.length > 0 ? Array.from(new Set(galleryToRemove)) : undefined;

    const payload: UpsertProductPayload = {
      sellingType: form.sellingType,
      brand: form.brand.trim() || null,
      variantGroup: form.variantGroup.trim() || null,
      releaseYear: releaseYearValue,
      storageCapacity: form.storageCapacity.trim() || null,
      memoryRam: form.memoryRam.trim() || null,
      color: form.color.trim() || null,
      variants: !isEdit && variantPayload.length > 0 ? variantPayload : undefined,
      name: form.name.trim(),
      slug: form.slug.trim() ? form.slug.trim() : null,
      sku: form.sku.trim(),
      description: form.description.trim(),
      shortDescription: form.shortDescription.trim() || null,
      price: priceValue,
      stock: stockValue,
      isPublished: form.isPublished,
      isFeaturedHome: form.isFeaturedHome,
      categoryId,
      imageAlt: form.imageAlt.trim() || null,
      removeImage: removeGalleryPayload?.includes(0) && !(galleryPayload?.[0] instanceof File),
      removeGallery: removeGalleryPayload,
      gallery: galleryPayload,
      // discounts
      discountEnabled: form.discountEnabled,
      discountType: form.discountEnabled ? form.discountType : undefined,
      discountValue:
        form.discountEnabled && form.discountValue.trim() !== ''
          ? Number(form.discountValue.replace(',', '.'))
          : undefined,
      discountStartsAt: form.discountEnabled && form.discountStartsAt ? form.discountStartsAt : undefined,
      discountEndsAt: form.discountEnabled && form.discountEndsAt ? form.discountEndsAt : undefined,
    };

    setSaving(true);
    setError(null);
    setMessage(null);

    const action = isEdit
      ? updateProduct(Number(productId), payload)
      : createProduct(payload);

    void action
      .then(() => {
        setMessage(isEdit ? 'Produit mis à jour.' : 'Produit créé.');
        if (!isEdit) {
          setForm(emptyForm);
          resetGalleryState();
          setVariantRows([]);
        }

        setTimeout(() => {
          navigate('/admin/catalog/products');
        }, 800);
      })
      .catch((err: any) => {
        setError(err?.message ?? 'Impossible d\'enregistrer le produit.');
      })
      .finally(() => {
        setSaving(false);
      });
  };

  const renderGallerySlot = (index: number) => {
    const preview = galleryPreviews[index];
    const hasExisting = initialGallery[index] !== null;
    const hasNewFile = galleryFiles[index] instanceof File;
    const markedForRemoval = galleryToRemove.includes(index);

    const labelText = preview ? 'Remplacer' : 'Ajouter';
    const removeLabel = hasNewFile ? 'Annuler' : 'Supprimer';

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
              onChange={(event) => handleGalleryFileChange(index, event.target.files)}
              hidden
            />
            {labelText}
          </label>
          {(preview || hasExisting) && (
            <button
              type="button"
              className="catalog-gallery-remove"
              onClick={() => handleRemoveGallery(index)}
            >
              {removeLabel}
            </button>
          )}
        </div>
        {index === 0 && (
          <p className="muted" style={{ marginTop: 6 }}>
            Image principale affichée sur les cartes produits.
          </p>
        )}
        {markedForRemoval && (
          <p className="catalog-gallery-alert">L'image sera supprimee lors de l'enregistrement.</p>
        )}
      </div>
    );
  };

  if (guardLoading) {
    return (
      <PageContainer title={isEdit ? 'Modifier un produit' : 'Nouveau produit'}>
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title={isEdit ? 'Modifier un produit' : 'Nouveau produit'}>
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={isEdit ? 'Modifier un produit' : 'Nouveau produit'}
      headerActions={
        <button
          type="button"
          className="register-form__submit"
          style={{ background: '#e5e7eb', color: '#111827' }}
          onClick={() => navigate('/admin/catalog/products')}
        >
          Retour à la liste
        </button>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {initialLoading ? (
        <p className="muted">Chargement du produit...</p>
      ) : (
        <form className="catalog-form-grid" onSubmit={handleSubmit}>
          <div className="catalog-form-row catalog-form-row--columns">
            <label>
              Nom du produit
              <input
                name="name"
                value={form.name}
                onChange={handleChange}
                maxLength={180}
                required
              />
            </label>
            <label>
              Slug (URL)
              <input
                name="slug"
                value={form.slug}
                onChange={handleChange}
                maxLength={200}
                placeholder="ex : solution-supervision"
              />
            </label>
            <label>
              SKU
              <input
                name="sku"
                value={form.sku}
                onChange={handleChange}
                maxLength={60}
                placeholder="Identifiant interne"
                required
              />
            </label>
            <label>
              Marque (optionnel)
              <input
                name="brand"
                value={form.brand}
                onChange={handleChange}
                maxLength={80}
                placeholder="Apple, Samsung, Xiaomi..."
              />
            </label>
            <label>
              Groupe de variantes / modèle parent (optionnel)
              <input
                name="variantGroup"
                value={form.variantGroup}
                onChange={handleChange}
                maxLength={120}
                placeholder="iPhone 17, Galaxy S25..."
              />
              <span className="muted">
                Sert à regrouper les variantes couleur d’un même modèle.
              </span>
            </label>
            <label>
              Année du modèle (optionnel)
              <input
                name="releaseYear"
                value={form.releaseYear}
                onChange={handleChange}
                inputMode="numeric"
                placeholder="2025"
              />
            </label>
            <label>
              Capacité de stockage (optionnel)
              <input
                name="storageCapacity"
                value={form.storageCapacity}
                onChange={handleChange}
                maxLength={40}
                placeholder="256 Go"
                list="storage-capacities"
              />
            </label>
            <label>
              Mémoire RAM (optionnel)
              <input
                name="memoryRam"
                value={form.memoryRam}
                onChange={handleChange}
                maxLength={40}
                placeholder="8 Go"
              />
            </label>
            <label>
              Couleur (optionnel)
              <input
                name="color"
                value={form.color}
                onChange={handleChange}
                maxLength={60}
                placeholder="Noir, Bleu, Vert..."
                list="color-options"
              />
            </label>
          </div>

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

          <div className="catalog-form-row">
            <span className="register-form__label">Remise</span>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <input
                  type="checkbox"
                  name="discountEnabled"
                  checked={form.discountEnabled}
                  onChange={handleChange}
                />
                Activer une remise
              </label>

              {form.discountEnabled && (
                <>
                  <label>
                    Type de remise
                    <select name="discountType" value={form.discountType} onChange={handleChange}>
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
                      onChange={handleChange}
                    />
                  </label>
                  <label>
                    Début (optionnel)
                    <input name="discountStartsAt" type="date" value={form.discountStartsAt} onChange={handleChange} />
                  </label>
                  <label>
                    Fin (optionnel)
                    <input name="discountEndsAt" type="date" value={form.discountEndsAt} onChange={handleChange} />
                  </label>
                </>
              )}
            </div>
          </div>

          <div className="catalog-form-row catalog-form-row--columns">
            <label>
              Type du produit
              <select name="sellingType" value={form.sellingType} onChange={handleChange}>
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
                onChange={handleChange}
                required
              />
            </label>
            <label>
              Stock de cette variante
              <input
                name="stock"
                type="number"
                min="0"
                value={form.stock}
                onChange={handleChange}
                required
              />
              <span className="muted">Le stock est géré par variante, donc par combinaison couleur / stockage.</span>
            </label>
            <label>
              Catégorie
              <select name="categoryId" value={form.categoryId} onChange={handleChange} required>
                <option value="">Sélectionnez une catégorie</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </label>
          </div>

          {!isEdit && (
            <div className="catalog-form-row">
              <span className="register-form__label">Variantes supplémentaires</span>
              <p className="muted" style={{ marginBottom: 12 }}>
                Chaque ligne crée un produit distinct avec son propre stock.
              </p>
              <div style={{ display: 'grid', gap: 12 }}>
                {variantRows.map((row, index) => (
                  <div
                    key={`${index}-${row.color}-${row.storageCapacity}`}
                    style={{
                      display: 'grid',
                      gridTemplateColumns: 'repeat(3, minmax(0, 1fr)) auto',
                      gap: 12,
                      alignItems: 'end',
                    }}
                  >
                    <label>
                      Couleur
                      <input
                        value={row.color}
                        onChange={(event) => updateVariantRow(index, 'color', event.target.value)}
                        list="color-options"
                        placeholder="Rouge"
                      />
                    </label>
                    <label>
                      Stockage
                      <input
                        value={row.storageCapacity}
                        onChange={(event) =>
                          updateVariantRow(index, 'storageCapacity', event.target.value)
                        }
                        list="storage-capacities"
                        placeholder="128 Go"
                      />
                    </label>
                    <label>
                      Stock
                      <input
                        type="number"
                        min="0"
                        value={row.stock}
                        onChange={(event) => updateVariantRow(index, 'stock', event.target.value)}
                      />
                    </label>
                    <button
                      type="button"
                      className="register-form__submit"
                      style={{ background: '#fee2e2', color: '#991b1b', height: 44 }}
                      onClick={() => removeVariantRow(index)}
                    >
                      Supprimer
                    </button>
                  </div>
                ))}
                <button
                  type="button"
                  className="register-form__submit"
                  style={{ background: '#e5e7eb', color: '#111827', width: 'fit-content' }}
                  onClick={addVariantRow}
                >
                  Ajouter une variante
                </button>
              </div>
            </div>
          )}

          <div className="catalog-form-row">
            <label>
              Description courte
              <textarea
                name="shortDescription"
                rows={2}
                maxLength={240}
                value={form.shortDescription}
                onChange={handleChange}
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
                onChange={handleChange}
                required
              />
            </label>
          </div>

          <div className="catalog-form-row">
            <span className="register-form__label">Galerie</span>
            <div className="catalog-gallery-grid">
              {Array.from({ length: GALLERY_SIZE }, (_, index) => renderGallerySlot(index))}
            </div>
          </div>

          <div className="catalog-form-row catalog-form-row--columns">
            <label>
              Texte alternatif (accessibilité)
              <input
                name="imageAlt"
                value={form.imageAlt}
                onChange={handleChange}
                maxLength={160}
                placeholder="Décrivez brièvement l'image principale"
              />
            </label>
          </div>

          <div className="catalog-form-row catalog-form-row--columns">
            <label style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <input
                type="checkbox"
                name="isPublished"
                checked={form.isPublished}
                onChange={handleChange}
              />
              Produit visible sur le site public
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <input
                type="checkbox"
                name="isFeaturedHome"
                checked={form.isFeaturedHome}
                onChange={handleChange}
              />
              Mettre en avant sur la page d'accueil
            </label>
          </div>

          <div className="catalog-form-actions">
            <button className="register-form__submit" type="submit" disabled={saving}>
              {saving ? 'Enregistrement...' : isEdit ? 'Mettre à jour' : 'Créer'}
            </button>
          </div>
        </form>
      )}
    </PageContainer>
  );
};





