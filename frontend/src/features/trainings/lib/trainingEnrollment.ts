export const trainingEnrollmentStatusClassName = (status: string) => {
  if (status === 'confirmed' || status === 'paid' || status === 'completed')
    return 'bg-emerald-100 text-emerald-800';
  if (status === 'cancelled') return 'bg-brand-50 text-stone-700';
  return 'bg-orange-100 text-orange-800';
};
