export const supportQueryKeys = {
  mine: () => ['support', 'mine'] as const,
  list: (page: number) => ['support', 'mine', 'list', { page }] as const,
  detail: (supportId: number | null) => ['support', 'mine', 'detail', supportId] as const,
};
