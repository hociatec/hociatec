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

export const DEFAULT_STORAGE_OPTIONS = ['64 Go', '128 Go', '256 Go', '512 Go', '1 To', '2 To'];

export const DEFAULT_COLOR_OPTIONS = [
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
