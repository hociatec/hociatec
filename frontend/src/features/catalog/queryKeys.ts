export const catalogQueryKeys = {
  publicCategories: () => ['catalog', 'public-categories'] as const,
  publicSearch: (params: Record<string, unknown>) => ['catalog', 'public-search', params] as const,
  publicCategory: (slug: string | null) => ['catalog', 'public-category', slug] as const,
  publicCategoryProducts: (params: Record<string, unknown>) =>
    ['catalog', 'public-category-products', params] as const,
  publicProduct: (slug: string | null) => ['catalog', 'public-product', slug] as const,
  publicProductColorVariants: (slug: string | null) =>
    ['catalog', 'public-product-color-variants', slug] as const,
  productVariants: (category: string, sellingType: string, group: string) =>
    ['catalog', 'product-variants', { category, sellingType, group }] as const,
  productReviews: (slug: string | null, page: number) =>
    ['catalog', 'product-reviews', { slug, page }] as const,
  homeProducts: () => ['catalog', 'home-products'] as const,
};
