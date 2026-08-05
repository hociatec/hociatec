import type { QuoteDto, QuoteInput, QuoteStatus } from '@/features/quotes/publicApi';
import type { QuoteItem } from '@/features/quotes/publicApi';

export type AdminQuoteFormState = {
  id?: number | undefined;
  number?: string | undefined;
  status: QuoteStatus;
  statusCode?: QuoteStatus | undefined;
  statusLabel?: string | undefined;
  customer: NonNullable<QuoteInput['customer']>;
  items: QuoteItem[];
  discountCents: number;
  shippingCents: number;
  conditions: string | null;
  validFrom: string | null;
  validUntil: string | null;
  totals?: QuoteDto['totals'] | undefined;
  createdAt?: string | undefined;
  updatedAt?: string | undefined;
  sentAt?: string | null | undefined;
  convertedOrder?: QuoteDto['convertedOrder'] | undefined;
  emailNotificationSent?: boolean | undefined;
  emailNotificationError?: string | null | undefined;
};
