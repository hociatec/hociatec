import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import { type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
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
  const payload = unwrapApiData(data, 'Impossible de charger la fidélité');
  return payload.loyalty as LoyaltyBalanceDto;
};

export const convertMyLoyalty = async (
  points: number,
): Promise<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }> => {
  const { data } = await httpClient.post<
    ApiResponse<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }>
  >('/api/loyalty/me/convert', { points });
  const payload = unwrapApiData(data, 'Impossible de convertir la fidélité');
  return {
    loyalty: payload.loyalty as LoyaltyBalanceDto,
    voucher: payload.voucher as MyVoucherDto,
  };
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
  const payload = unwrapApiData(data, 'Impossible de charger la fidélité admin');
  return { items: payload.items, meta: payload.meta };
};

export const updateAdminLoyaltyCustomer = async (
  customerId: number,
  points: number,
): Promise<ApiMutationResult<AdminLoyaltyCustomerDto>> => {
  const { data } = await httpClient.patch<ApiResponse<{ customer: AdminLoyaltyCustomerDto }>>(
    `/api/admin/loyalty/customers/${customerId}`,
    { points },
  );
  const payload = unwrapApiData(data, 'Impossible de mettre à jour le solde');
  return { data: payload.customer, message: data.message };
};
