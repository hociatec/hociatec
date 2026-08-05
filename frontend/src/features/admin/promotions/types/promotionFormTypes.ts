export type PromotionFormState = {
  name: string;
  slug: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  audienceKey: string;
  minimumCartTotalEuros: string;
  registeredDays: string;
  minimumOrders: string;
  inactiveDays: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
};
