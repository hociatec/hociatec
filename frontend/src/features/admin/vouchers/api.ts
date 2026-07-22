import { httpClient } from '@/shared/lib/httpClient';

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

export const fetchVouchers = async (): Promise<VoucherDto[]> => {
  const { data } = await httpClient.get<{ data: { items: VoucherDto[] } }>('/api/admin/vouchers');
  return data.data.items;
};

export const fetchVoucher = async (voucherId: number): Promise<VoucherDto> => {
  const { data } = await httpClient.get<{ data: { voucher: VoucherDto } }>(`/api/admin/vouchers/${voucherId}`);
  return data.data.voucher;
};

export const createVoucher = async (payload: VoucherPayload): Promise<VoucherDto> => {
  const { data } = await httpClient.post<{ data: { voucher: VoucherDto } }>('/api/admin/vouchers', payload);
  return data.data.voucher;
};

export const updateVoucher = async (voucherId: number, payload: VoucherPayload): Promise<VoucherDto> => {
  const { data } = await httpClient.put<{ data: { voucher: VoucherDto } }>(`/api/admin/vouchers/${voucherId}`, payload);
  return data.data.voucher;
};

export const deleteVoucher = async (voucherId: number): Promise<void> => {
  await httpClient.delete(`/api/admin/vouchers/${voucherId}`);
};
