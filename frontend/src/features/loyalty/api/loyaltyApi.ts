import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { MyVoucherDto } from '@/features/vouchers/api/vouchersApi';

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
  const { data } = await httpClient.get<ApiResponse<{ loyalty: LoyaltyBalanceDto }>>('/api/loyalty/me');
  if (isApiOk(data)) {
    return data.data?.loyalty as LoyaltyBalanceDto;
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger la fidélité');
};

export const convertMyLoyalty = async (
  points: number,
): Promise<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }> => {
  const { data } = await httpClient.post<ApiResponse<{ loyalty: LoyaltyBalanceDto; voucher: MyVoucherDto }>>(
    '/api/loyalty/me/convert',
    { points },
  );
  if (isApiOk(data)) {
    return {
      loyalty: data.data?.loyalty as LoyaltyBalanceDto,
      voucher: data.data?.voucher as MyVoucherDto,
    };
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de convertir la fidélité');
};

export const fetchAdminLoyaltyCustomers = async (search = ''): Promise<AdminLoyaltyCustomerDto[]> => {
  const query = new URLSearchParams();
  if (search.trim() !== '') {
    query.set('search', search.trim());
  }

  const { data } = await httpClient.get<ApiResponse<{ items: AdminLoyaltyCustomerDto[] }>>(
    `/api/admin/loyalty${query.toString() !== '' ? `?${query.toString()}` : ''}`,
  );
  if (isApiOk(data)) {
    return data.data.items;
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de charger la fidélité admin');
};

export const updateAdminLoyaltyCustomer = async (
  customerId: number,
  points: number,
): Promise<AdminLoyaltyCustomerDto> => {
  const { data } = await httpClient.patch<ApiResponse<{ customer: AdminLoyaltyCustomerDto }>>(
    `/api/admin/loyalty/customers/${customerId}`,
    { points },
  );
  if (isApiOk(data)) {
    return data.data.customer;
  }

  throw new Error(data.status === 'error' ? data.message : 'Impossible de mettre à jour le solde');
};
