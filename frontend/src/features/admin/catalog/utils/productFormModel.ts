import type { CatalogBrand, CatalogProduct, UpsertProductPayload } from '@/features/catalog/adminApi';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
import { omitUndefinedProperties } from '@/shared/lib/object';
import type { ProductFormState, VariantRowState } from './productFormConfig';
import { parseNonNegativeDecimal, parseNonNegativeInteger } from '@/shared/lib/parsers';
import {
  buildVariantIdentityKey,
  extractNumericValue,
  formatVariantConflictLabel,
  parseProductPrice,
  normalizeTextValue,
} from './productFormUtils';

export const buildProductFormState = (product: CatalogProduct): ProductFormState => ({
  name: product.name,
  slug: product.slug,
  sku: product.sku,
  price: formatEuroInputFromCents(product.priceCents),
  sellingType: product.sellingType,
  brand: product.brand ?? '',
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
  discountType: product.discount?.type === 'fixed_cents' ? 'fixed' : 'percent',
  discountValue:
    product.discount?.type === 'fixed_cents'
      ? formatEuroInputFromCents(product.discount?.value ?? 0)
      : (product.discount?.value ?? '').toString(),
  discountStartsAt: product.discount?.startsAt ? product.discount.startsAt.substring(0, 10) : '',
  discountEndsAt: product.discount?.endsAt ? product.discount.endsAt.substring(0, 10) : '',
});

interface BuildProductPayloadInput {
  form: ProductFormState;
  brands: CatalogBrand[];
  variantRows: VariantRowState[];
  groupVariants: CatalogProduct[];
  currentProductId: number | null;
  galleryFiles: Array<File | null>;
  galleryToRemove: number[];
}

type ProductPayloadResult =
  { payload: UpsertProductPayload; error?: never } | { payload?: never; error: string };

export const buildProductPayload = ({
  form,
  brands,
  variantRows,
  groupVariants,
  currentProductId,
  galleryFiles,
  galleryToRemove,
}: BuildProductPayloadInput): ProductPayloadResult => {
  const priceValue = parseProductPrice(form.price);
  const stockValue = parseNonNegativeInteger(form.stock);
  const releaseYearValue =
    form.releaseYear.trim() === '' ? null : parseNonNegativeInteger(form.releaseYear, Number.NaN);
  const categoryId = parseNonNegativeInteger(form.categoryId);
  const variantPayload = variantRows
    .map((row) => {
      const stock = parseNonNegativeInteger(row.stock);
      const price = row.price.trim() === '' ? priceValue : parseProductPrice(row.price);
      const storageCapacity =
        row.storageCapacity.trim() === '' ? null : parseNonNegativeInteger(row.storageCapacity);
      return {
        color: row.color.trim() || null,
        storageCapacity:
          storageCapacity !== null && !Number.isNaN(storageCapacity)
            ? `${storageCapacity} Go`
            : null,
        stock,
        price,
      };
    })
    .filter((row) => row.color !== null || row.storageCapacity !== null);
  const discountValue =
    form.discountEnabled && form.discountValue.trim() !== ''
      ? parseNonNegativeDecimal(form.discountValue, Number.NaN)
      : undefined;

  if (Number.isNaN(priceValue)) return { error: 'Le prix indiqué est invalide.' };
  if (Number.isNaN(stockValue))
    return { error: 'Le stock doit être un entier positif.' };
  if (
    releaseYearValue !== null &&
    (Number.isNaN(releaseYearValue) || releaseYearValue < 2000 || releaseYearValue > 2100)
  ) {
    return { error: 'L’année du modèle doit être comprise entre 2000 et 2100.' };
  }
  if (Number.isNaN(categoryId)) return { error: 'Merci de sélectionner une catégorie.' };
  if (form.discountEnabled && form.discountValue.trim() !== '' && Number.isNaN(discountValue)) {
    return { error: 'La remise doit être un nombre valide.' };
  }
  if (
    form.discountEnabled &&
    form.discountType === 'percent' &&
    discountValue !== undefined &&
    discountValue > 100
  ) {
    return { error: 'La remise en pourcentage doit être comprise entre 0 et 100.' };
  }
  if (variantPayload.some((row) => Number.isNaN(row.stock))) {
    return { error: 'Le stock des variantes doit être un entier positif.' };
  }
  if (variantPayload.some((row) => Number.isNaN(row.price))) {
    return { error: 'Le prix des variantes doit être un nombre valide.' };
  }

  const selectedBrand =
    form.brand.trim() === ''
      ? null
      : (brands.find((brand) => normalizeTextValue(brand.name) === normalizeTextValue(form.brand)) ??
        null);
  const storageCapacityValue =
    form.storageCapacity.trim() === '' ? null : parseNonNegativeInteger(form.storageCapacity);
  const memoryRamValue =
    form.memoryRam.trim() === '' ? null : parseNonNegativeInteger(form.memoryRam);

  if (selectedBrand === null)
    return { error: 'La marque est obligatoire. Recherchez puis cochez une marque existante.' };
  if (
    storageCapacityValue !== null &&
    (Number.isNaN(storageCapacityValue) || storageCapacityValue < 1 || storageCapacityValue > 4096)
  ) {
    return { error: 'Le stockage doit être un nombre compris entre 1 et 4096.' };
  }
  if (
    memoryRamValue !== null &&
    (Number.isNaN(memoryRamValue) || memoryRamValue < 1 || memoryRamValue > 256)
  ) {
    return { error: 'La mémoire RAM doit être un nombre compris entre 1 et 256.' };
  }

  const normalizedStorage =
    storageCapacityValue !== null && !Number.isNaN(storageCapacityValue)
      ? `${storageCapacityValue} Go`
      : null;
  const existingVariantKeys = new Set(
    groupVariants
      .filter((variant) => variant.id !== currentProductId)
      .map((variant) => buildVariantIdentityKey(variant.color, variant.storageCapacity)),
  );
  const currentVariantKey = buildVariantIdentityKey(form.color.trim() || null, normalizedStorage);
  if (existingVariantKeys.has(currentVariantKey)) {
    return {
      error: `La variante ${formatVariantConflictLabel(form.color, normalizedStorage)} existe déjà.`,
    };
  }

  const incomingVariantKeys = new Set<string>([currentVariantKey]);
  for (const row of variantPayload) {
    const key = buildVariantIdentityKey(row.color, row.storageCapacity);
    if (existingVariantKeys.has(key) || incomingVariantKeys.has(key)) {
      return {
        error: `La variante ${formatVariantConflictLabel(row.color, row.storageCapacity)} existe déjà.`,
      };
    }
    incomingVariantKeys.add(key);
  }

  const galleryPayload = galleryFiles.some(Boolean) ? galleryFiles : undefined;
  const removeGalleryPayload =
    galleryToRemove.length > 0 ? Array.from(new Set(galleryToRemove)) : undefined;
  return {
    payload: omitUndefinedProperties({
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
      discountValue: form.discountEnabled ? discountValue : undefined,
      discountStartsAt:
        form.discountEnabled && form.discountStartsAt ? form.discountStartsAt : undefined,
      discountEndsAt: form.discountEnabled && form.discountEndsAt ? form.discountEndsAt : undefined,
    }) as UpsertProductPayload,
  };
};
