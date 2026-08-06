import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

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

export const fetchMyVouchers = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<MyVoucherDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: MyVoucherDto[]; meta: PaginationMeta }>>(
    '/api/vouchers/me',
    { params: { page, perPage } },
  );

  if (isApiOk(data)) {
    return data.data;
  }

  throw new Error(extractApiErrorMessage(data, 'Impossible de charger vos bons de réduction'));
};
