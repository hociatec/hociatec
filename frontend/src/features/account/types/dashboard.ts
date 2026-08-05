import type { AppointmentItem } from '@/features/appointments/publicApi';
import type { PendingReviewDto } from '@/features/orders/publicApi';
import type { LoyaltyBalanceDto } from '@/features/loyalty/publicApi';
import type { QuoteDto } from '@/features/quotes/publicApi';
import type { TrainingEnrollmentDto } from '@/features/trainings/publicApi';

export type DashboardLoadState = 'loading' | 'success' | 'error';
export type DashboardConversionState = 'idle' | 'saving';
export type DashboardActionKind = 'order' | 'appointment' | 'quote' | 'training';

export interface DashboardAction {
  kind: DashboardActionKind;
  title: string;
  detail: string;
  to: string;
}

export interface DashboardData {
  quotes: QuoteDto[];
  appointments: AppointmentItem[];
  trainings: TrainingEnrollmentDto[];
  pendingReviews: PendingReviewDto[];
  loyalty: LoyaltyBalanceDto;
}
