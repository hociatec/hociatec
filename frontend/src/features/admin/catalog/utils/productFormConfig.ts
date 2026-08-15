export type AttributeRowState = {
  code: string;
  label: string;
  value: string;
};

export type ProductFormState = {
  name: string;
  slug: string;
  sku: string;
  salePrice: string;
  rentalPrice: string;
  availableForSale: boolean;
  availableForRental: boolean;
  brand: string;
  variantGroup: string;
  releaseYear: string;
  attributes: AttributeRowState[];
  stock: string;
  shortDescription: string;
  description: string;
  categoryId: string;
  isPublished: boolean;
  isFeaturedHome: boolean;
  imageAlt: string;
  discountEnabled: boolean;
  discountType: 'percent' | 'fixed';
  discountValue: string;
  discountStartsAt: string;
  discountEndsAt: string;
};

export type VariantRowState = {
  attributes: AttributeRowState[];
  stock: string;
  salePrice: string;
  rentalPrice: string;
};

export const emptyProductForm: ProductFormState = {
  name: '',
  slug: '',
  sku: '',
  salePrice: '0',
  rentalPrice: '',
  availableForSale: true,
  availableForRental: false,
  brand: '',
  variantGroup: '',
  releaseYear: '',
  attributes: [],
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

export const GALLERY_SIZE = 4;
