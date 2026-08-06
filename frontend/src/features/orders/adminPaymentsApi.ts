import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
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
  throw new Error(extractApiErrorMessage(data, data.message));
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
  if (isApiOk(data)) {
    return { items: data.data.items.map(parseAdminPayment), meta: data.data.meta };
  }
  throw new Error(extractApiErrorMessage(data, 'Impossible de charger les paiements'));
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
  throw new Error(extractApiErrorMessage(data, 'Impossible de charger le paiement'));
};
