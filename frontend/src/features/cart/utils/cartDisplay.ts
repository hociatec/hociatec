import { formatEuroCents } from '@/shared/lib/formatters';

export const formatCartPrice = formatEuroCents;

export const formatPromotionValue = (
  discountType: 'percent' | 'fixed_cents',
  discountValue: number,
) => (discountType === 'percent' ? `${discountValue}%` : formatCartPrice(discountValue));
