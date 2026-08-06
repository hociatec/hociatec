import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

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

export const fetchMyAddresses = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<AddressDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AddressDto[]; meta: PaginationMeta }>>(
    '/api/addresses/me',
    { params: { page, perPage } },
  );
  if (isApiOk(data)) {
    return data.data;
  }
  throw new Error(extractApiErrorMessage(data, 'Impossible de charger les adresses'));
};

export const createAddress = async (
  payload: Omit<AddressDto, 'id' | 'isDefault'> & { isDefault?: boolean },
): Promise<AddressDto> => {
  const { data } = await httpClient.post<ApiResponse<{ address: AddressDto }>>(
    '/api/addresses',
    payload,
  );
  if (isApiOk(data)) {
    return data.data.address;
  }
  throw new Error(extractApiErrorMessage(data, "Impossible de créer l'adresse"));
};

export const updateAddress = async (
  id: number,
  payload: Omit<AddressDto, 'id' | 'isDefault'>,
): Promise<AddressDto> => {
  const { data } = await httpClient.put<ApiResponse<{ address: AddressDto }>>(
    `/api/addresses/${id}`,
    payload,
  );
  if (isApiOk(data)) {
    return data.data.address;
  }
  throw new Error(extractApiErrorMessage(data, "Impossible de mettre à jour l'adresse"));
};

export const deleteAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ message: string }>>(
    `/api/addresses/${id}`,
  );
  if (isApiOk(data)) return;
  throw new Error(extractApiErrorMessage(data, "Impossible de supprimer l'adresse"));
};

export const setDefaultAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.put<ApiResponse<{ message: string }>>(
    `/api/addresses/${id}/default`,
  );
  if (isApiOk(data)) return;
  throw new Error(
    extractApiErrorMessage(data, "Impossible de définir l'adresse par défaut"),
  );
};
