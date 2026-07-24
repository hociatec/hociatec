import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { AdminPaymentDetailDto, AdminPaymentDto, AdminPaymentLiveStripeDto } from './orderTypes';

export const fetchAdminPayments = async (
  status: 'all' | 'open' | 'paid' | 'expired' | 'failed' = 'all',
  q = '',
): Promise<AdminPaymentDto[]> => {
  const query = new URLSearchParams();
  if (status && status !== 'all') {
    query.set('status', status);
  }
  if (q.trim() !== '') {
    query.set('q', q.trim());
  }

  const { data } = await httpClient.get<ApiResponse<{ items: AdminPaymentDto[] }>>(
    `/api/admin/payments${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return (data.data?.items ?? []) as AdminPaymentDto[];
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les paiements';
  throw new Error(message);
};

export const fetchAdminPaymentById = async (
  paymentId: number,
): Promise<{ payment: AdminPaymentDetailDto; liveStripe: AdminPaymentLiveStripeDto | null }> => {
  const { data } = await httpClient.get<ApiResponse<{
    payment: AdminPaymentDetailDto;
    liveStripe: AdminPaymentLiveStripeDto | null;
  }>>(`/api/admin/payments/${paymentId}`);
  if (isApiOk(data)) {
    return {
      payment: data.data?.payment as AdminPaymentDetailDto,
      liveStripe: (data.data?.liveStripe ?? null) as AdminPaymentLiveStripeDto | null,
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger le paiement';
  throw new Error(message);
};

