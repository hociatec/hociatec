import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type {
  AdminPaymentDetailDto,
  AdminPaymentDto,
  AdminPaymentLiveStripeDto,
  OrderStatusOptionDto,
} from './orderTypes';
import { parseAdminPayment, parseAdminPaymentDetail, parseAdminPaymentLiveStripe } from './orderValidation';

export const fetchAdminPaymentMetadata = async (): Promise<{ statuses: OrderStatusOptionDto[] }> => {
  const { data } = await httpClient.get<ApiResponse<{ statuses: OrderStatusOptionDto[] }>>('/api/admin/payments/metadata');
  if (isApiOk(data)) return data.data;
  throw new Error(data.message);
};

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
    return data.data.items.map(parseAdminPayment);
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les paiements';
  throw new Error(message);
};

export const fetchAdminPaymentById = async (
  paymentId: number,
): Promise<{ payment: AdminPaymentDetailDto; liveStripe: AdminPaymentLiveStripeDto | null }> => {
  const { data } = await httpClient.get<
    ApiResponse<{
      payment: AdminPaymentDetailDto;
      liveStripe: AdminPaymentLiveStripeDto | null;
    }>
  >(`/api/admin/payments/${paymentId}`);
  if (isApiOk(data)) {
    return {
      payment: parseAdminPaymentDetail(data.data.payment),
      liveStripe: parseAdminPaymentLiveStripe(data.data.liveStripe),
    };
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger le paiement';
  throw new Error(message);
};
