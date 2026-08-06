import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { MyVoucherDto } from '@/features/vouchers/publicApi';

export interface LoyaltyBalanceDto {
  points: number;
  euroCents: number;
  pointsPerEuroEarned: number;
  pointsPerEuroConverted: number;
}

export interface AdminLoyaltyCustomerDto {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  fullName: string;
  points: number;
  euroCents: number;
  createdAt: string;
}

export const fetchMyLoyalty = async (): Promise<LoyaltyBalanceDto> => {
  const { data } =
    await httpClient.get<ApiResponse<{ loyalty: LoyaltyBalanceDto }>>('/api/loyalty/me');
  if (isApiOk(data)) {
    return data.data?.loyalty as LoyaltyBalanceDto;
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger la fidélité');
};

export const convertMyLoyalty = async (
  points: number,
): Promise<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }> => {
  const { data } = await httpClient.post<
    ApiResponse<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }>
  >('/api/loyalty/me/convert', { points });
  if (isApiOk(data)) {
    return {
      loyalty: data.data?.loyalty as LoyaltyBalanceDto,
      voucher: data.data?.voucher as MyVoucherDto,
    };
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de convertir la fidélité');
};

export const fetchAdminLoyaltyCustomers = async (
  search = '',
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<AdminLoyaltyCustomerDto>> => {
  const query = new URLSearchParams();
  if (search.trim() !== '') {
    query.set('search', search.trim());
  }
  query.set('page', String(page));
  query.set('perPage', String(perPage));

  const { data } = await httpClient.get<ApiResponse<{ items: AdminLoyaltyCustomerDto[]; meta: PaginationMeta }>>(
    `/api/admin/loyalty${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return { items: data.data.items, meta: data.data.meta };
  }

  throw new Error(
    data.status === 'error' ? data.message : 'Impossible de charger la fidélité admin',
  );
};

export const updateAdminLoyaltyCustomer = async (
  customerId: number,
  points: number,
): Promise<ApiMutationResult<AdminLoyaltyCustomerDto>> => {
  const { data } = await httpClient.patch<ApiResponse<{ customer: AdminLoyaltyCustomerDto }>>(
    `/api/admin/loyalty/customers/${customerId}`,
    { points },
  );
  if (isApiOk(data)) {
    return { data: data.data.customer, message: data.message };
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de mettre à jour le solde');
};
