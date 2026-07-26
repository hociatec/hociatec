import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

export interface MyVoucherDto {
  id: number;
  name: string;
  code: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
  sentAt?: string | null;
  createdAt: string;
  updatedAt: string;
}

export const fetchMyVouchers = async (): Promise<MyVoucherDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: MyVoucherDto[] }>>('/api/vouchers/me');

  if (isApiOk(data)) {
    return data.data.items;
  }

  const message =
    data.status === 'error' ? data.message : 'Impossible de charger vos bons de réduction';
  throw new Error(message);
};
