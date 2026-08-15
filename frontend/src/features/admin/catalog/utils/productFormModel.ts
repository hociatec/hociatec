import type {
  CatalogBrand,
  CatalogCategory,
  CatalogProduct,
  UpsertProductPayload,
} from '@/features/catalog/adminApi';
import { formatEuroInputFromCents } from '@/shared/lib/formatters';
import { omitUndefinedProperties } from '@/shared/lib/object';
import type { ProductFormState, VariantRowState } from './productFormConfig';
import { parseNonNegativeDecimal, parseNonNegativeInteger } from '@/shared/lib/parsers';
import {
  buildVariantIdentityKey,
  formatVariantConflictLabel,
  parseProductPrice,
  normalizeTextValue,
  normalizeAttributeRows,
  validateAttributeValuesAgainstDefinitions,
  validateRequiredAttributes,
} from './productFormUtils';

export const buildProductFormState = (product: CatalogProduct): ProductFormState => ({
  name: product.name,
  slug: product.slug,
  sku: product.sku,
  salePrice: formatEuroInputFromCents(product.salePriceCents ?? 0),
  rentalPrice: product.rentalPriceCents !== null && product.rentalPriceCents !== undefined ? formatEuroInputFromCents(product.rentalPriceCents) : '',
  availableForSale: product.availableForSale ?? product.sellingType === 'sale',
  availableForRental: product.availableForRental ?? product.sellingType === 'rental',
  brand: product.brand ?? '',
  variantGroup: product.variantGroup ?? '',
  releaseYear: product.releaseYear?.toString() ?? '',
  attributes: normalizeAttributeRows(product.attributes ?? []),
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
  categories: CatalogCategory[];
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
  categories,
  variantRows,
  groupVariants,
  currentProductId,
  galleryFiles,
  galleryToRemove,
}: BuildProductPayloadInput): ProductPayloadResult => {
  const normalizedAttributes = normalizeAttributeRows(form.attributes);
  const selectedCategory = categories.find((category) => category.id.toString() === form.categoryId) ?? null;
  const categoryDefinitions = selectedCategory?.attributeDefinitions ?? [];
  const salePriceValue =
    form.salePrice.trim() === '' ? null : parseProductPrice(form.salePrice);
  const rentalPriceValue =
    form.rentalPrice.trim() === '' ? null : parseProductPrice(form.rentalPrice);
  const stockValue = parseNonNegativeInteger(form.stock);
  const releaseYearValue =
    form.releaseYear.trim() === '' ? null : parseNonNegativeInteger(form.releaseYear, Number.NaN);
  const categoryId = parseNonNegativeInteger(form.categoryId);
  const variantPayload = variantRows
    .map((row) => {
      const attributes = normalizeAttributeRows(row.attributes);
      const stock = parseNonNegativeInteger(row.stock);
      const salePrice =
        row.salePrice.trim() === ''
          ? salePriceValue
          : parseProductPrice(row.salePrice);
      const rentalPrice =
        row.rentalPrice.trim() === ''
          ? rentalPriceValue
          : parseProductPrice(row.rentalPrice);

      return {
        attributes,
        stock,
        salePrice,
        rentalPrice,
      };
    })
    .filter((row) => row.attributes.length > 0);
  const discountValue =
    form.discountEnabled && form.discountValue.trim() !== ''
      ? parseNonNegativeDecimal(form.discountValue, Number.NaN)
      : undefined;

  if (salePriceValue !== null && Number.isNaN(salePriceValue)) return { error: 'Le prix de vente indiqué est invalide.' };
  if (rentalPriceValue !== null && Number.isNaN(rentalPriceValue)) return { error: 'Le prix mensuel de location indiqué est invalide.' };
  if (!form.availableForSale && !form.availableForRental) {
    return { error: 'Activez au moins la vente ou la location.' };
  }
  if (form.availableForSale && salePriceValue === null) {
    return { error: 'Le prix de vente est obligatoire.' };
  }
  if (form.availableForRental && rentalPriceValue === null) {
    return { error: 'Le prix mensuel de location est obligatoire.' };
  }
  if (Number.isNaN(stockValue))
    return { error: 'Le stock doit être un entier positif.' };
  if (
    releaseYearValue !== null &&
    (Number.isNaN(releaseYearValue) || releaseYearValue < 2000 || releaseYearValue > 2100)
  ) {
    return { error: 'L’année du modèle doit être comprise entre 2000 et 2100.' };
  }
  if (Number.isNaN(categoryId)) return { error: 'Merci de sélectionner une catégorie.' };
  const missingMainAttributeError = validateRequiredAttributes(normalizedAttributes, categoryDefinitions);
  if (missingMainAttributeError) {
    return { error: missingMainAttributeError };
  }
  const invalidMainAttributeValueError = validateAttributeValuesAgainstDefinitions(
    normalizedAttributes,
    categoryDefinitions,
  );
  if (invalidMainAttributeValueError) {
    return { error: invalidMainAttributeValueError };
  }
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
  if (variantPayload.some((row) => row.salePrice !== null && Number.isNaN(row.salePrice))) {
    return { error: 'Le prix de vente des variantes doit être un nombre valide.' };
  }
  if (variantPayload.some((row) => row.rentalPrice !== null && Number.isNaN(row.rentalPrice))) {
    return { error: 'Le prix location des variantes doit être un nombre valide.' };
  }
  const missingVariantAttributeDefinition = variantPayload.find((row) =>
    validateRequiredAttributes(row.attributes, categoryDefinitions),
  );
  if (missingVariantAttributeDefinition) {
    return {
      error:
        validateRequiredAttributes(missingVariantAttributeDefinition.attributes, categoryDefinitions) ??
        'Une variante ne respecte pas les attributs requis de la catégorie.',
    };
  }
  const invalidVariantAttributeValueError = variantPayload
    .map((row) => validateAttributeValuesAgainstDefinitions(row.attributes, categoryDefinitions))
    .find((error): error is string => Boolean(error));
  if (invalidVariantAttributeValueError) {
    return { error: invalidVariantAttributeValueError };
  }
  if (form.availableForSale && variantPayload.some((row) => row.salePrice === null)) {
    return { error: 'Chaque variante doit avoir un prix de vente ou hériter du prix de vente principal.' };
  }
  if (form.availableForRental && variantPayload.some((row) => row.rentalPrice === null)) {
    return { error: 'Chaque variante doit avoir un prix de location ou hériter du prix location principal.' };
  }

  const selectedBrand =
    form.brand.trim() === ''
      ? null
      : (brands.find((brand) => normalizeTextValue(brand.name) === normalizeTextValue(form.brand)) ??
        null);

  if (selectedBrand === null)
    return { error: 'La marque est obligatoire. Recherchez puis cochez une marque existante.' };
  const existingVariantKeys = new Set(
    groupVariants
      .filter((variant) => variant.id !== currentProductId)
      .map((variant) => buildVariantIdentityKey(variant.attributes ?? [])),
  );
  const currentVariantKey = buildVariantIdentityKey(normalizedAttributes);
  if (normalizedAttributes.length > 0 && existingVariantKeys.has(currentVariantKey)) {
    return {
      error: `La variante ${formatVariantConflictLabel(normalizedAttributes)} existe déjà.`,
    };
  }

  const incomingVariantKeys = new Set<string>(
    normalizedAttributes.length > 0 ? [currentVariantKey] : [],
  );
  for (const row of variantPayload) {
    const key = buildVariantIdentityKey(row.attributes);
    if (existingVariantKeys.has(key) || incomingVariantKeys.has(key)) {
      return {
        error: `La variante ${formatVariantConflictLabel(row.attributes)} existe déjà.`,
      };
    }
    incomingVariantKeys.add(key);
  }

  const galleryPayload = galleryFiles.some(Boolean) ? galleryFiles : undefined;
  const removeGalleryPayload =
    galleryToRemove.length > 0 ? Array.from(new Set(galleryToRemove)) : undefined;
  return {
    payload: omitUndefinedProperties({
      salePrice: form.availableForSale ? salePriceValue : null,
      rentalPrice: form.availableForRental ? rentalPriceValue : null,
      availableForSale: form.availableForSale,
      availableForRental: form.availableForRental,
      brandId: selectedBrand.id,
      variantGroup: null,
      releaseYear: releaseYearValue,
      attributes: normalizedAttributes,
      variants: variantPayload.length > 0 ? variantPayload : undefined,
      name: form.name.trim(),
      slug: form.slug.trim() ? form.slug.trim() : null,
      sku: form.sku.trim(),
      description: form.description.trim(),
      shortDescription: form.shortDescription.trim() || null,
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
