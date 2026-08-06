import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type {
  AdminPaymentDetailDto,
  AdminPaymentDto,
  AdminPaymentLiveStripeDto,
  OrderStatusOptionDto,
} from './orderTypes';
import { parseAdminPayment, parseAdminPaymentDetail, parseAdminPaymentLiveStripe } from './orderValidation';

export const fetchAdminPaymentMetadata = async (): Promise<{ statuses: OrderStatusOptionDto[] }> => {
  const { data } = await httpClient.get<ApiResponse<{ statuses: OrderStatusOptionDto[] }>>('/api/admin/payments/metadata');
  return unwrapApiData(data, data.message ?? 'Réponse API invalide.');
};

export const fetchAdminPayments = async (
  status: 'all' | 'open' | 'paid' | 'expired' | 'failed' = 'all',
  q = '',
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<AdminPaymentDto>> => {
  const query = new URLSearchParams();
  query.set('page', String(page));
  query.set('perPage', String(perPage));
  if (status && status !== 'all') {
    query.set('status', status);
  }
  if (q.trim() !== '') {
    query.set('q', q.trim());
  }

  const { data } = await httpClient.get<ApiResponse<{ items: AdminPaymentDto[]; meta: PaginationMeta }>>(
    `/api/admin/payments${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  const payload = unwrapApiData(data, 'Impossible de charger les paiements');
  return { items: payload.items.map(parseAdminPayment), meta: payload.meta };
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
  const payload = unwrapApiData(data, 'Impossible de charger le paiement');
  return {
    payment: parseAdminPaymentDetail(payload.payment),
    liveStripe: parseAdminPaymentLiveStripe(payload.liveStripe),
  };
};
