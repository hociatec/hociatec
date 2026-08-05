import type { QuoteDto, QuoteInput, QuoteStatus } from '@/features/quotes/types/quoteTypes';
import type { QuoteItem } from '@/features/quotes/utils/quoteFormUtils';

export type AdminQuoteFormState = {
  id?: number;
  number?: string;
  status: QuoteStatus;
  statusCode?: QuoteStatus;
  statusLabel?: string;
  customer: NonNullable<QuoteInput['customer']>;
  items: QuoteItem[];
  discountCents: number;
  shippingCents: number;
  conditions: string | null;
  validFrom: string | null;
  validUntil: string | null;
  totals?: QuoteDto['totals'];
  createdAt?: string;
  updatedAt?: string;
  sentAt?: string | null;
  convertedOrder?: QuoteDto['convertedOrder'];
  emailNotificationSent?: boolean;
  emailNotificationError?: string | null;
};
