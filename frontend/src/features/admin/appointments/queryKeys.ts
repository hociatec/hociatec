export const adminAppointmentQueryKeys = {
  prestations: () => ['admin', 'appointments', 'prestations'] as const,
  prestation: (id: number | null) => ['admin', 'appointments', 'prestation', id] as const,
  configuration: () => ['admin', 'appointments', 'configuration'] as const,
};
