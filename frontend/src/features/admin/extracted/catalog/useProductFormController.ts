import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { type ChangeEvent, type FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  createProduct,
  deleteProduct,
  fetchAdminBrands,
  fetchAdminCategories,
  fetchAdminProduct,
  fetchAdminProducts,
  updateProduct,
  type CatalogBrand,
  type CatalogCategory,
  type CatalogProduct,
  type UpsertProductPayload,
} from '@/features/catalog/api';
import {
  emptyProductForm,
  GALLERY_SIZE,
  type ProductFormState,
  type VariantRowState,
} from '@/features/admin/catalog/utils/productFormConfig';
import {
  buildVariantIdentityKey,
  extractNumericValue,
  formatVariantConflictLabel,
  slugify,
} from '@/features/admin/catalog/utils/productFormUtils';

export const useProductFormController = () => {
  const { productId } = useParams();
  const isEdit = useMemo(() => Boolean(productId), [productId]);
  const currentProductId = useMemo(() => (productId ? Number(productId) : null), [productId]);
  const navigate = useNavigate();

  const [form, setForm] = useState<ProductFormState>(emptyProductForm);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [brands, setBrands] = useState<CatalogBrand[]>([]);
  const [brandQuery, setBrandQuery] = useState('');
  const [saving, setSaving] = useState(false);
  const [deletingVariantId, setDeletingVariantId] = useState<number | null>(null);
  const [initialLoading, setInitialLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [variantRows, setVariantRows] = useState<VariantRowState[]>([]);
  const [groupVariants, setGroupVariants] = useState<CatalogProduct[]>([]);
  const [currentVariantPosition, setCurrentVariantPosition] = useState(1);
  const [galleryFiles, setGalleryFiles] = useState<Array<File | null>>(Array.from({ length: GALLERY_SIZE }, () => null));
  const [galleryPreviews, setGalleryPreviews] = useState<Array<string | null>>(Array.from({ length: GALLERY_SIZE }, () => null));
  const [initialGallery, setInitialGallery] = useState<Array<string | null>>(Array.from({ length: GALLERY_SIZE }, () => null));
  const [galleryToRemove, setGalleryToRemove] = useState<number[]>([]);
  const galleryObjectUrlsRef = useRef<Array<string | null>>(Array.from({ length: GALLERY_SIZE }, () => null));

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
    setInitialLoading(true);
    setError(null);
    setMessage(null);

    const load = async () => {
      try {
        const [categoryList, brandList, product, adminProducts] = await Promise.all([
          fetchAdminCategories(),
          fetchAdminBrands(),
          isEdit ? fetchAdminProduct(Number(productId)) : Promise.resolve(null),
          isEdit ? fetchAdminProducts() : Promise.resolve([]),
        ]);

        setCategories(categoryList);
        setBrands(brandList);

        if (product) {
          hydrateFromProduct(product);
          if (product.variantGroup) {
            const relatedVariants = adminProducts
              .filter((item) => item.variantGroup === product.variantGroup)
              .sort((left, right) => {
                const leftPosition = left.variantPosition ?? Number.MAX_SAFE_INTEGER;
                const rightPosition = right.variantPosition ?? Number.MAX_SAFE_INTEGER;

                if (leftPosition !== rightPosition) {
                  return leftPosition - rightPosition;
                }

                return left.id - right.id;
              });

            setGroupVariants(relatedVariants.length > 0 ? relatedVariants : [product]);
          } else {
            setGroupVariants([product]);
          }
        } else if (categoryList.length > 0) {
          setForm((prev) => ({ ...prev, categoryId: categoryList[0].id.toString() }));
          setBrandQuery('');
          setGroupVariants([]);
        }
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger les données du produit.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isEdit, productId]);

  const hydrateFromProduct = (product: CatalogProduct) => {
    const productBrand = product.brand ?? '';
    setCurrentVariantPosition(product.variantPosition ?? 1);
    setForm({
      name: product.name,
      slug: product.slug,
      sku: product.sku,
      price: (product.priceCents / 100).toString(),
      sellingType: product.sellingType,
      brand: productBrand,
      variantGroup: product.variantGroup ?? '',
      releaseYear: product.releaseYear?.toString() ?? '',
      storageCapacity: extractNumericValue(product.storageCapacity),
      memoryRam: extractNumericValue(product.memoryRam),
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
      discountValue: product.discount?.type === 'fixed_cents'
        ? ((product.discount?.value ?? 0) / 100).toString()
        : (product.discount?.value ?? '').toString(),
      discountStartsAt: product.discount?.startsAt ? product.discount.startsAt.substring(0, 10) : '',
      discountEndsAt: product.discount?.endsAt ? product.discount.endsAt.substring(0, 10) : '',
    });
    setBrandQuery(productBrand);

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
        return { ...prev, name: value, slug: shouldSyncSlug ? generatedSlug : prev.slug };
      }

      if (name === 'slug') {
        return { ...prev, slug: slugify(value) };
      }

      return { ...prev, [name]: value };
    });
  };

  const handleChange = (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
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
        setGalleryPreviews((previous) => previous.map((item, itemIndex) => itemIndex === index ? objectUrl : item));
        setGalleryToRemove((previous) => previous.filter((value) => value !== index));
      } else {
        next[index] = null;
        const fallback = initialGallery[index];
        setGalleryPreviews((previous) => previous.map((item, itemIndex) => itemIndex === index ? fallback : item));
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
      setGalleryPreviews((previous) => previous.map((item, itemIndex) => itemIndex === index ? null : item));
      setGalleryToRemove((previous) =>
        initialGallery[index]
          ? Array.from(new Set([...previous, index]))
          : previous.filter((value) => value !== index),
      );

      return next;
    });
  };

  const handleBrandQueryChange = (value: string) => {
    setBrandQuery(value);
    setForm((prev) => {
      const selectedBrand =
        prev.brand.trim() === ''
          ? null
          : brands.find((brand) => brand.name.toLowerCase() === prev.brand.trim().toLowerCase()) ?? null;

      if (selectedBrand !== null && selectedBrand.name.toLowerCase() === value.trim().toLowerCase()) {
        return prev;
      }

      return { ...prev, brand: '' };
    });
  };

  const handleBrandSelection = (brand: CatalogBrand) => {
    setBrandQuery(brand.name);
    setForm((prev) => ({ ...prev, brand: brand.name }));
  };

  const filteredBrands = useMemo(() => {
    const search = brandQuery.trim().toLowerCase();

    if (search === '') {
      return form.brand
        ? brands.filter((brand) => brand.name.toLowerCase() === form.brand.trim().toLowerCase())
        : [];
    }

    return brands.filter((brand) => brand.name.toLowerCase().includes(search)).slice(0, 8);
  }, [brandQuery, brands, form.brand]);

  const formatVariantDetails = (product: CatalogProduct) => {
    const details = [product.color, product.storageCapacity].filter(
      (value): value is string => Boolean(value && value.trim() !== ''),
    );

    return details.length > 0 ? details.join(' • ') : 'Aucune précision';
  };

  const addVariantRow = () => {
    setVariantRows((previous) => [...previous, { color: '', storageCapacity: '', stock: '0' }]);
  };

  const updateVariantRow = (index: number, field: keyof VariantRowState, value: string) => {
    setVariantRows((previous) => previous.map((row, rowIndex) => (rowIndex === index ? { ...row, [field]: value } : row)));
  };

  const removeVariantRow = (index: number) => {
    setVariantRows((previous) => previous.filter((_, rowIndex) => rowIndex !== index));
  };

  const handleDeleteVariant = (variant: CatalogProduct) => {
    if (groupVariants.length <= 1 || deletingVariantId !== null) return;

    if (!window.confirm(`Supprimer la variante ${formatVariantDetails(variant)} ?`)) return;

    setDeletingVariantId(variant.id);
    setError(null);
    setMessage(null);
    void deleteProduct(variant.id)
      .then(() => {
        const remainingVariants = groupVariants.filter((item) => item.id !== variant.id);
        if (variant.id === currentProductId) {
          const nextVariant = remainingVariants[0] ?? null;
          void navigate(nextVariant ? `/admin/catalog/products/${nextVariant.id}/edit` : '/admin/catalog/products');
          return;
        }

        setGroupVariants(remainingVariants);
        setMessage('Variante supprimée.');
      })
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de supprimer la variante.')))
      .finally(() => setDeletingVariantId(null));
  };

  const parsePrice = (value: string) => {
    const parsed = Number(value.replace(',', '.'));
    return Number.isNaN(parsed) ? -1 : parsed;
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (saving) return;

    const priceValue = parsePrice(form.price);
    const stockValue = Number.parseInt(form.stock, 10);
    const releaseYearValue = form.releaseYear.trim() === '' ? null : Number.parseInt(form.releaseYear.trim(), 10);
    const categoryId = Number.parseInt(form.categoryId, 10);
    const variantPayload = variantRows
      .map((row) => {
        const stock = Number.parseInt(row.stock, 10);
        const storageCapacity = row.storageCapacity.trim() === '' ? null : Number.parseInt(row.storageCapacity.trim(), 10);
        return {
          color: row.color.trim() || null,
          storageCapacity: storageCapacity !== null && !Number.isNaN(storageCapacity) ? `${storageCapacity} Go` : null,
          stock,
        };
      })
      .filter((row) => row.color !== null || row.storageCapacity !== null);

    if (Number.isNaN(priceValue) || priceValue < 0) return setError('Le prix indiqué est invalide.');
    if (Number.isNaN(stockValue) || stockValue < 0) return setError('Le stock doit être un entier positif.');
    if (releaseYearValue !== null && (Number.isNaN(releaseYearValue) || releaseYearValue < 2000 || releaseYearValue > 2100)) return setError('L’année du modèle doit être comprise entre 2000 et 2100.');
    if (Number.isNaN(categoryId)) return setError('Merci de sélectionner une catégorie.');
    if (variantPayload.some((row) => Number.isNaN(row.stock) || row.stock < 0)) return setError('Le stock des variantes doit être un entier positif.');

    const selectedBrand = form.brand.trim() === ''
      ? null
      : brands.find((brand) => brand.name.toLowerCase() === form.brand.trim().toLowerCase()) ?? null;
    const storageCapacityValue = form.storageCapacity.trim() === '' ? null : Number.parseInt(form.storageCapacity.trim(), 10);
    const memoryRamValue = form.memoryRam.trim() === '' ? null : Number.parseInt(form.memoryRam.trim(), 10);

    if (selectedBrand === null) return setError('La marque est obligatoire. Recherchez puis cochez une marque existante.');
    if (storageCapacityValue !== null && (Number.isNaN(storageCapacityValue) || storageCapacityValue < 1 || storageCapacityValue > 4096)) return setError('Le stockage doit être un nombre compris entre 1 et 4096.');
    if (memoryRamValue !== null && (Number.isNaN(memoryRamValue) || memoryRamValue < 1 || memoryRamValue > 256)) return setError('La mémoire RAM doit être un nombre compris entre 1 et 256.');

    const normalizedStorage = storageCapacityValue !== null && !Number.isNaN(storageCapacityValue) ? `${storageCapacityValue} Go` : null;
    const existingVariantKeys = new Set(
      groupVariants
        .filter((variant) => variant.id !== currentProductId)
        .map((variant) => buildVariantIdentityKey(variant.color, variant.storageCapacity)),
    );
    const currentVariantKey = buildVariantIdentityKey(form.color.trim() || null, normalizedStorage);

    if (existingVariantKeys.has(currentVariantKey)) {
      return setError(`La variante ${formatVariantConflictLabel(form.color, normalizedStorage)} existe déjà.`);
    }

    const incomingVariantKeys = new Set<string>([currentVariantKey]);
    for (const row of variantPayload) {
      const key = buildVariantIdentityKey(row.color, row.storageCapacity);
      if (existingVariantKeys.has(key) || incomingVariantKeys.has(key)) {
        return setError(`La variante ${formatVariantConflictLabel(row.color, row.storageCapacity)} existe déjà.`);
      }
      incomingVariantKeys.add(key);
    }

    const galleryPayload = galleryFiles.some(Boolean) ? galleryFiles : undefined;
    const removeGalleryPayload = galleryToRemove.length > 0 ? Array.from(new Set(galleryToRemove)) : undefined;
    const payload: UpsertProductPayload = {
      sellingType: form.sellingType,
      brandId: selectedBrand.id,
      variantGroup: null,
      releaseYear: releaseYearValue,
      storageCapacity: normalizedStorage,
      memoryRam: memoryRamValue !== null ? `${memoryRamValue} Go` : null,
      color: form.color.trim() || null,
      variants: variantPayload.length > 0 ? variantPayload : undefined,
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
      discountEnabled: form.discountEnabled,
      discountType: form.discountEnabled ? form.discountType : undefined,
      discountValue: form.discountEnabled && form.discountValue.trim() !== '' ? Number(form.discountValue.replace(',', '.')) : undefined,
      discountStartsAt: form.discountEnabled && form.discountStartsAt ? form.discountStartsAt : undefined,
      discountEndsAt: form.discountEnabled && form.discountEndsAt ? form.discountEndsAt : undefined,
    };

    setSaving(true);
    setError(null);
    setMessage(null);
    const action = isEdit ? updateProduct(Number(productId), payload) : createProduct(payload);
    void action
      .then(() => {
        setMessage(isEdit ? 'Produit mis à jour.' : 'Produit créé.');
        if (!isEdit) {
          setForm(emptyProductForm);
          resetGalleryState();
          setVariantRows([]);
        }
        setTimeout(() => navigate('/admin/catalog/products'), 800);
      })
      .catch((err) => setError(getHttpErrorMessage(err, "Impossible d'enregistrer le produit.")))
      .finally(() => setSaving(false));
  };

  const navigateToProductList = () => navigate('/admin/catalog/products');
  const navigateToVariant = (variantId: number) => {
    setError(null);
    setMessage(null);
    void navigate(`/admin/catalog/products/${variantId}/edit`);
  };

  return {
    addVariantRow,
    brandQuery,
    categories,
    currentProductId,
    currentVariantPosition,
    deletingVariantId,
    error,
    filteredBrands,
    form,
    formatVariantDetails,
    galleryFiles,
    galleryPreviews,
    galleryToRemove,
    groupVariants,
    handleBrandQueryChange,
    handleBrandSelection,
    handleChange,
    handleDeleteVariant,
    handleGalleryFileChange,
    handleRemoveGallery,
    handleSubmit,
    initialGallery,
    initialLoading,
    isEdit,
    message,
    navigateToProductList,
    navigateToVariant,
    removeVariantRow,
    saving,
    updateVariantRow,
    variantRows,
  };
};
