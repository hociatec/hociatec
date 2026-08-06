export const adminCatalogQueryKeys = {
  products: () => ['admin', 'catalog', 'products'] as const,
  productsPage: (params: Record<string, unknown>) =>
    [...adminCatalogQueryKeys.products(), params] as const,
  productForm: (id: number | null) => ['admin', 'catalog', 'product-form', id] as const,
  categories: () => ['admin', 'catalog', 'categories'] as const,
  category: (id: number | null) => ['admin', 'catalog', 'category', id] as const,
  brands: () => ['admin', 'catalog', 'brands'] as const,
  brand: (id: number | null) => ['admin', 'catalog', 'brand', id] as const,
};
