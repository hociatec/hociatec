import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

export interface AddressDto {
  id: number;
  name: string;
  address: string;
  postalCode: string;
  city: string;
  company?: string | null;
  companySiren?: string | null;
  companyVatNumber?: string | null;
  purchaseOrderNumber?: string | null;
  isDefault: boolean;
}

export const fetchMyAddresses = async (): Promise<AddressDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AddressDto[] }>>('/api/addresses/me');
  if (isApiOk(data)) {
    return data.data.items;
  }
  const message = data.status === 'error' ? data.message : 'Impossible de charger les adresses';
  throw new Error(message);
};

export const createAddress = async (payload: Omit<AddressDto, 'id' | 'isDefault'> & { isDefault?: boolean }): Promise<AddressDto> => {
  const { data } = await httpClient.post<ApiResponse<{ address: AddressDto }>>('/api/addresses', payload);
  if (isApiOk(data)) {
    return data.data.address;
  }
  const message = data.status === 'error' ? data.message : 'Impossible de créer l\'adresse';
  throw new Error(message);
};

export const updateAddress = async (id: number, payload: Omit<AddressDto, 'id' | 'isDefault'>): Promise<AddressDto> => {
  const { data } = await httpClient.put<ApiResponse<{ address: AddressDto }>>(`/api/addresses/${id}`, payload);
  if (isApiOk(data)) {
    return data.data.address;
  }
  const message = data.status === 'error' ? data.message : 'Impossible de mettre à jour l\'adresse';
  throw new Error(message);
};

export const deleteAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ message: string }>>(`/api/addresses/${id}`);
  if (isApiOk(data)) return;
  const message = data.status === 'error' ? data.message : 'Impossible de supprimer l\'adresse';
  throw new Error(message);
};

export const setDefaultAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.put<ApiResponse<{ message: string }>>(`/api/addresses/${id}/default`);
  if (isApiOk(data)) return;
  const message = data.status === 'error' ? data.message : 'Impossible de définir l\'adresse par défaut';
  throw new Error(message);
};
