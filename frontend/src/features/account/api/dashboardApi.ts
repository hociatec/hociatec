import { fetchMyAppointments } from '@/features/appointments/publicApi';
import { fetchPendingReviews } from '@/features/orders/publicApi';
import { convertMyLoyalty, fetchMyLoyalty } from '@/features/loyalty/publicApi';
import { fetchMyQuotes } from '@/features/quotes/publicApi';
import { fetchMyTrainingEnrollments } from '@/features/trainings/publicApi';
import type { DashboardData } from '@/features/account/types/dashboard';

export const emptyLoyalty = {
  points: 0,
  euroCents: 0,
  pointsPerEuroEarned: 10,
  pointsPerEuroConverted: 100,
};

export const fetchDashboardData = async (): Promise<{
  data: DashboardData;
  hasError: boolean;
}> => {
  const results = await Promise.allSettled([
    fetchMyQuotes(),
    fetchMyAppointments(),
    fetchMyTrainingEnrollments(),
    fetchPendingReviews(),
    fetchMyLoyalty(),
  ]);
  const [quotes, appointments, trainings, pendingReviews, loyalty] = results;

  return {
    data: {
      quotes: quotes.status === 'fulfilled' ? quotes.value.items : [],
      appointments: appointments.status === 'fulfilled' ? (appointments.value.upcoming ?? []) : [],
      trainings: trainings.status === 'fulfilled' ? trainings.value.items : [],
      pendingReviews: pendingReviews.status === 'fulfilled' ? pendingReviews.value : [],
      loyalty: loyalty.status === 'fulfilled' ? loyalty.value : emptyLoyalty,
    },
    hasError: [quotes, appointments, pendingReviews].some((result) => result.status === 'rejected'),
  };
};

export { convertMyLoyalty };
