import { lazyPage } from '../routeLazy';

export const CategoriesListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/CategoriesListPage'),
  'CategoriesListPage',
);
export const BrandsListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/BrandsListPage'),
  'BrandsListPage',
);
export const BrandFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/BrandFormPage'),
  'BrandFormPage',
);
export const CategoryFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/CategoryFormPage'),
  'CategoryFormPage',
);
export const ProductsListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/ProductsListPage'),
  'ProductsListPage',
);
export const ProductFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/ProductFormPage'),
  'ProductFormPage',
);
