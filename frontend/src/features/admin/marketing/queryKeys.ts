export const adminMarketingQueryKeys = {
  overview: () => ['admin', 'marketing', 'overview'] as const,
  templates: () => ['admin', 'marketing', 'templates'] as const,
  campaigns: () => ['admin', 'marketing', 'campaigns'] as const,
  segments: (type: string) => ['admin', 'marketing', 'segments', type] as const,
  template: (id: number | null) => ['admin', 'marketing', 'template', id] as const,
  templateDetail: (id: number | null, segmentType: string) =>
    ['admin', 'marketing', 'template-detail', { id, segmentType }] as const,
  campaignFormOptions: () => ['admin', 'marketing', 'campaign-form-options'] as const,
};
