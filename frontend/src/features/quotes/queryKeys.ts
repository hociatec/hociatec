export const quoteQueryKeys = {
  mine: () => ['quotes', 'mine'] as const,
  mineDetail: (id: number | null) => ['quotes', 'mine-detail', id] as const,
  publicServices: () => ['quotes', 'public-services'] as const,
  publicService: (id: number | null) => ['quotes', 'public-service', id] as const,
  catalogProducts: (q: string) => ['quotes', 'catalog-products', { q }] as const,
};

export const adminQuoteQueryKeys = {
  metadata: () => ['admin', 'quotes', 'metadata'] as const,
  list: (search: string, status: string) => ['admin', 'quotes', { search, status }] as const,
  detail: (id: number | null) => ['admin', 'quotes', 'detail', id] as const,
  services: () => ['admin', 'quotes', 'services'] as const,
  service: (id: number | null) => ['admin', 'quotes', 'service', id] as const,
  formOptions: (id: number | null) => ['admin', 'quotes', 'form-options', id] as const,
};
