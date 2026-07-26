import type { AppointmentItem } from '@/features/appointments/types/appointments';
import type { PendingReviewDto } from '@/features/orders/api';
import type { LoyaltyBalanceDto } from '@/features/loyalty/api/loyaltyApi';
import type { QuoteDto } from '@/features/quotes/types/quoteTypes';
import type { TrainingEnrollmentDto } from '@/features/trainings/api/trainingsApi';

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
