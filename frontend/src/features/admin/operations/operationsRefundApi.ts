import { httpClient } from '@/shared/lib/httpClient';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import {
  rethrowApiError,
  unwrap,
  type RefundRequestDto,
} from './operationsApiShared';

export const fetchRefunds = async (page = 1, perPage = 10): Promise<PaginatedResult<RefundRequestDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: RefundRequestDto[]; meta: PaginationMeta }>>(
    '/api/admin/operations/refunds',
    { params: { page, perPage } },
  );
  return unwrap(data, 'Impossible de charger les remboursements');
};

export const createRefund = async (payload: {
  orderId: number;
  amountCents: number;
  paymentId?: number | null;
  reason?: string;
  internalNotes?: string;
}): Promise<RefundRequestDto> => {
  const { data } = await httpClient.post<ApiResponse<{ item: RefundRequestDto }>>(
    '/api/admin/operations/refunds',
    payload,
  );
  return unwrap(data, 'Impossible de créer le remboursement').item;
};

export const updateRefund = async (
  id: number,
  payload: Partial<Pick<RefundRequestDto, 'status' | 'stripeRefundId' | 'internalNotes'>>,
): Promise<RefundRequestDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ item: RefundRequestDto }>>(
    `/api/admin/operations/refunds/${id}`,
    payload,
  );
  return unwrap(data, 'Impossible de mettre à jour le remboursement').item;
};

export const processStripeRefund = async (
  id: number,
  payload: { confirmation: string; paymentIntentId?: string },
): Promise<RefundRequestDto> => {
  try {
    const { data } = await httpClient.post<
      ApiResponse<{ item: RefundRequestDto; stripeRefund: Record<string, unknown> }>
    >(`/api/admin/operations/refunds/${id}/process-stripe`, payload);
    return unwrap(data, 'Impossible de déclencher le remboursement Stripe').item;
  } catch (error) {
    return rethrowApiError(error, 'Impossible de déclencher le remboursement Stripe');
  }
};
