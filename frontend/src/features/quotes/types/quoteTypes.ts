import type { QuoteStatus } from '@/shared/contracts/statuses';

export type { QuoteStatus };
export interface QuoteCustomerDto {
  name: string | null;
  email: string | null;
  company: string | null;
  address: string | null;
}
export interface QuoteLineTotalsDto {
  ht: number;
  vat: number;
  ttc: number;
}
export interface QuoteItemDto {
  id: number;
  type: QuoteItemInput['type'];
  productId: number | null;
  serviceId: number | null;
  name: string;
  description: string | null;
  unit: string | null;
  quantity: number;
  unitPriceCents: number;
  vatRate: number;
  discountCents: number;
  lineTotals: QuoteLineTotalsDto;
}
export interface QuoteDto {
  id: number;
  number: string;
  status: string;
  statusCode: QuoteStatus;
  statusLabel: string;
  customer: QuoteCustomerDto;
  items: QuoteItemDto[];
  discountCents: number;
  shippingCents: number;
  conditions: string | null;
  validFrom: string | null;
  validUntil: string | null;
  totals: QuoteLineTotalsDto;
  createdAt: string;
  updatedAt: string;
  sentAt: string | null;
  convertedOrder: { id: number; number: string } | null;
  emailNotificationSent?: boolean | undefined;
  emailNotificationError?: string | null | undefined;
}
export interface QuoteServiceDto {
  id: number;
  title: string;
  description: string | null;
  unit: string | null;
  isFeaturedHome: boolean;
  imageUrl?: string | null;
  imageAlt?: string | null;
  durationValue: number | null;
  durationUnit: 'hour' | 'day' | null;
  durationLabel: string | null;
  priceCents: number;
  vatRate: number;
}
export interface AdminQuoteEmailDto {
  sent: boolean;
  statusCode?: QuoteStatus | undefined;
  statusLabel?: string | undefined;
  to?: string | undefined;
  attachmentIncluded?: boolean | undefined;
  transport?: string | undefined;
  message?: string | undefined;
}
export interface QuoteToOrderDto {
  order: { id: number; number: string } & Record<string, unknown>;
  emailNotificationSent?: boolean | undefined;
  emailNotificationError?: string | null | undefined;
}
export interface DeleteDto {
  id?: number;
  deleted?: boolean;
  removed?: boolean;
}
export interface QuoteItemInput {
  id?: number;
  type: 'service' | 'product' | 'custom';
  productId?: number | null;
  serviceId?: number | null;
  name: string;
  description?: string | null;
  unit?: string | null;
  quantity: number;
  unitPriceCents: number;
  vatRate: number;
  discountCents?: number;
}
export interface QuoteInput {
  status?: QuoteStatus;
  customer?: {
    name?: string | null;
    email?: string | null;
    company?: string | null;
    address?: string | null;
  };
  items: QuoteItemInput[];
  discountCents?: number;
  shippingCents?: number;
  conditions?: string | null;
  validFrom?: string | null;
  validUntil?: string | null;
}
export interface QuoteServiceInput {
  title: string;
  description?: string;
  unit?: string;
  isFeaturedHome?: boolean;
  image?: File | null;
  imageUrl?: string;
  imageAlt?: string;
  durationValue?: number | '';
  durationUnit?: 'hour' | 'day' | '';
  price: number;
  vatRate: number;
}
