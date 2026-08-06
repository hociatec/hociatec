import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

export type VoucherDto = {
  id: number;
  name: string;
  code: string;
  description: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  isActive: boolean;
  startsAt: string | null;
  endsAt: string | null;
  createdAt?: string;
  updatedAt?: string;
};

export type Voucher = VoucherDto;

export type VoucherPayload = {
  name: string;
  code: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
};

export const fetchVouchers = async (page = 1, perPage = 10): Promise<PaginatedResult<VoucherDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: VoucherDto[]; meta: PaginationMeta }>>('/api/admin/vouchers', {
    params: { page, perPage },
  });
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return data.data;
};

export const fetchVoucher = async (voucherId: number): Promise<VoucherDto> => {
  const { data } = await httpClient.get<{ data: { voucher: VoucherDto } }>(
    `/api/admin/vouchers/${voucherId}`,
  );
  return data.data.voucher;
};

export const createVoucher = async (payload: VoucherPayload): Promise<ApiMutationResult<VoucherDto>> => {
  const { data } = await httpClient.post<ApiResponse<{ voucher: VoucherDto }>>(
    '/api/admin/vouchers',
    payload,
  );
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data.voucher, message: data.message };
};

export const updateVoucher = async (
  voucherId: number,
  payload: VoucherPayload,
): Promise<ApiMutationResult<VoucherDto>> => {
  const { data } = await httpClient.put<ApiResponse<{ voucher: VoucherDto }>>(
    `/api/admin/vouchers/${voucherId}`,
    payload,
  );
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data.voucher, message: data.message };
};

export const deleteVoucher = async (voucherId: number): Promise<ApiMutationResult<{ deleted: boolean }>> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(`/api/admin/vouchers/${voucherId}`);
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data, message: data.message };
};
