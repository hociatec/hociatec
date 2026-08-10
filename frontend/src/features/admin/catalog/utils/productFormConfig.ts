export type ProductFormState = {
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
  discountValue: string;
  discountStartsAt: string;
  discountEndsAt: string;
};

export type VariantRowState = {
  color: string;
  storageCapacity: string;
  stock: string;
  price: string;
};

export const emptyProductForm: ProductFormState = {
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

export const GALLERY_SIZE = 4;
