export const appointmentQueryKeys = {
  prestations: () => ['appointments', 'prestations'] as const,
  availability: (prestationId: number | null, start: string, end: string) =>
    ['appointments', 'availability', { prestationId, start, end }] as const,
  mine: () => ['appointments', 'mine'] as const,
};
