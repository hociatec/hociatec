export const adminRentalQueryKeys = {
  list: (page: number, perPage: number, q: string, timeline: string, requestStatus: string, requestType: string) =>
    ['admin', 'rentals', 'list', { page, perPage, q, timeline, requestStatus, requestType }] as const,
  detail: (id: number | null) => ['admin', 'rentals', 'detail', id] as const,
};
