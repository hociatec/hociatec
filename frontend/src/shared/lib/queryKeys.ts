export const betaQueryKeys = {
  profile: () => ['beta', 'profile'] as const,
  profileForm: () => ['beta', 'profile-form'] as const,
  profileChoices: () => ['beta', 'profile-choices'] as const,
  campaigns: () => ['beta', 'campaigns'] as const,
  reports: () => ['beta', 'reports'] as const,
  reportsPage: (page: number) => [...betaQueryKeys.reports(), { page }] as const,
  report: (id: number | null) => ['beta', 'report', id] as const,
  reportComments: (id: number | null) => ['beta', 'report-comments', id] as const,
  reportCommentsPage: (id: number | null, page: number) =>
    [...betaQueryKeys.reportComments(id), { page }] as const,
};

export const favoriteQueryKeys = {
  all: () => ['favorites'] as const,
};

export const voucherQueryKeys = {
  mine: () => ['vouchers', 'mine'] as const,
};

export const newsQueryKeys = {
  articles: () => ['news', 'articles'] as const,
  articlesPage: (page: number, q: string) => [...newsQueryKeys.articles(), { page, q }] as const,
  article: (slug: string) => ['news', 'article', slug] as const,
  comments: (slug: string) => ['news', 'comments', slug] as const,
  commentsPage: (slug: string, page: number) => [...newsQueryKeys.comments(slug), { page }] as const,
};

export const systemQueryKeys = {
  status: () => ['system', 'status'] as const,
};

export const trainingQueryKeys = {
  publicCatalog: () => ['trainings', 'public-catalog'] as const,
  publicDetail: (slug: string) => ['trainings', 'public-detail', slug] as const,
  myEnrollments: () => ['trainings', 'my-enrollments'] as const,
};

export const adminTrainingQueryKeys = {
  trainings: () => ['admin', 'trainings', 'items'] as const,
  training: (id: number | null) => ['admin', 'trainings', 'item', id] as const,
  trainingForm: (id: number | null) => ['admin', 'trainings', 'form', id] as const,
  categories: () => ['admin', 'trainings', 'categories'] as const,
  sessions: () => ['admin', 'trainings', 'sessions'] as const,
  sessionForm: (id: number | null) => ['admin', 'trainings', 'session-form', id] as const,
  enrollments: () => ['admin', 'trainings', 'enrollments'] as const,
  overview: () => ['admin', 'trainings', 'overview'] as const,
};

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

export const auditQueryKeys = {
  metadata: () => ['audits', 'metadata'] as const,
  mine: () => ['audits', 'mine'] as const,
  mineDetail: (id: number | null) => ['audits', 'mine-detail', id] as const,
  adminList: () => ['admin', 'audits'] as const,
  adminDetail: (id: number | null) => ['admin', 'audits', 'detail', id] as const,
};

export const addressQueryKeys = {
  mine: () => ['addresses', 'mine'] as const,
};

export const cartQueryKeys = {
  checkoutAddresses: () => ['cart', 'checkout-addresses'] as const,
};

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

export const adminDashboardQueryKeys = {
  dashboard: () => ['admin', 'dashboard'] as const,
};

export const adminCustomerQueryKeys = {
  list: (search: string, sort: string) => ['admin', 'customers', { search, sort }] as const,
  detail: (id: number | null) => ['admin', 'customers', 'detail', id] as const,
  vouchers: (id: number | null) => ['admin', 'customers', 'vouchers', id] as const,
};

export const adminAppointmentQueryKeys = {
  prestations: () => ['admin', 'appointments', 'prestations'] as const,
  prestation: (id: number | null) => ['admin', 'appointments', 'prestation', id] as const,
  configuration: () => ['admin', 'appointments', 'configuration'] as const,
};

export const appointmentQueryKeys = {
  prestations: () => ['appointments', 'prestations'] as const,
  availability: (prestationId: number | null, start: string, end: string) =>
    ['appointments', 'availability', { prestationId, start, end }] as const,
  mine: () => ['appointments', 'mine'] as const,
};

export const orderQueryKeys = {
  mine: () => ['orders', 'mine'] as const,
  detail: (id: number | null) => ['orders', 'detail', id] as const,
  pendingReviews: () => ['orders', 'pending-reviews'] as const,
  checkoutSession: (sessionId: string | null) => ['orders', 'checkout-session', sessionId] as const,
};

export const adminOrderQueryKeys = {
  metadata: () => ['admin', 'orders', 'metadata'] as const,
  list: (status: string, health: string) => ['admin', 'orders', { status, health }] as const,
  detail: (id: number | null) => ['admin', 'orders', 'detail', id] as const,
};

export const adminPaymentQueryKeys = {
  metadata: () => ['admin', 'payments', 'metadata'] as const,
  list: (status: string, search: string) => ['admin', 'payments', { status, search }] as const,
  detail: (id: number | null) => ['admin', 'payments', 'detail', id] as const,
};

export const adminNewsQueryKeys = {
  list: (q: string) => ['admin', 'news', { q }] as const,
  detail: (id: number | null) => ['admin', 'news', 'detail', id] as const,
};

export const adminPromotionQueryKeys = {
  overview: () => ['admin', 'promotions', 'overview'] as const,
  audiences: () => ['admin', 'promotions', 'audiences'] as const,
  detail: (id: number | null) => ['admin', 'promotions', 'detail', id] as const,
};

export const adminMarketingQueryKeys = {
  overview: () => ['admin', 'marketing', 'overview'] as const,
  templates: () => ['admin', 'marketing', 'templates'] as const,
  segments: (type: string) => ['admin', 'marketing', 'segments', type] as const,
  template: (id: number | null) => ['admin', 'marketing', 'template', id] as const,
  templateDetail: (id: number | null, segmentType: string) =>
    ['admin', 'marketing', 'template-detail', { id, segmentType }] as const,
  campaigns: () => ['admin', 'marketing', 'campaigns'] as const,
  campaignFormOptions: () => ['admin', 'marketing', 'campaign-form-options'] as const,
};

export const adminBackupQueryKeys = {
  status: () => ['admin', 'backups', 'status'] as const,
};

export const adminLoyaltyQueryKeys = {
  customers: (search: string) => ['admin', 'loyalty', 'customers', { search }] as const,
};

export const adminOperationsQueryKeys = {
  overview: () => ['admin', 'operations', 'overview'] as const,
};

export const adminVoucherQueryKeys = {
  list: () => ['admin', 'vouchers'] as const,
  detail: (id: number | null) => ['admin', 'vouchers', 'detail', id] as const,
};

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

export const homeQueryKeys = {
  featuredServices: () => ['home', 'featured-services'] as const,
  latestNews: () => ['home', 'latest-news'] as const,
};

export const accountQueryKeys = {
  dashboard: () => ['account', 'dashboard'] as const,
  notifications: () => ['account', 'notifications'] as const,
  notificationsReadState: () => ['account', 'notifications-read-state'] as const,
};

export const profileQueryKeys = {
  communicationPreferences: () => ['profile', 'communication-preferences'] as const,
};

export const searchQueryKeys = {
  global: (query: string, limit: number) => ['search', 'global', { query, limit }] as const,
};

export const tradeInQueryKeys = {
  metadata: () => ['trade-ins', 'metadata'] as const,
  mine: () => ['trade-ins', 'mine'] as const,
};

export const adminBetaQueryKeys = {
  campaigns: () => ['admin', 'beta', 'campaigns'] as const,
  testers: (search: string, status: string) => ['admin', 'beta', 'testers', { search, status }] as const,
  profileChoices: () => ['admin', 'beta', 'profile-choices'] as const,
  bugReports: () => ['admin', 'beta', 'bug-reports'] as const,
  bugReportsList: (filters: Record<string, unknown>) =>
    [...adminBetaQueryKeys.bugReports(), filters] as const,
  bugReport: (id: number | null) => ['admin', 'beta', 'bug-report', id] as const,
  bugReportDashboard: () => ['admin', 'beta', 'bug-report-dashboard'] as const,
  bugReportActivity: (id: number | null) => ['admin', 'beta', 'bug-report-activity', id] as const,
  bugReportComments: (id: number | null) => ['admin', 'beta', 'bug-report-comments', id] as const,
  bugReportCommentsPage: (id: number | null, page: number) =>
    [...adminBetaQueryKeys.bugReportComments(id), { page }] as const,
};
