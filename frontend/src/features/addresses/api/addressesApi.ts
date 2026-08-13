import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

export interface AddressDto {
  id: number;
  type: 'personal' | 'professional';
  name: string;
  address: string;
  addressComplement?: string | null;
  postalCode: string;
  city: string;
  company?: string | null;
  companySiren?: string | null;
  companyVatNumber?: string | null;
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
  return unwrapApiData(data, 'Impossible de charger les adresses');
};

export const createAddress = async (
  payload: Omit<AddressDto, 'id' | 'isDefault'> & { isDefault?: boolean },
): Promise<AddressDto> => {
  const { data } = await httpClient.post<ApiResponse<{ address: AddressDto }>>(
    '/api/addresses',
    payload,
  );
  const responsePayload = unwrapApiData(data, "Impossible de créer l'adresse");
  return responsePayload.address;
};

export const updateAddress = async (
  id: number,
  payload: Omit<AddressDto, 'id' | 'isDefault'>,
): Promise<AddressDto> => {
  const { data } = await httpClient.put<ApiResponse<{ address: AddressDto }>>(
    `/api/addresses/${id}`,
    payload,
  );
  const responsePayload = unwrapApiData(data, "Impossible de mettre à jour l'adresse");
  return responsePayload.address;
};

export const deleteAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.delete<ApiResponse<{ message: string }>>(
    `/api/addresses/${id}`,
  );
  unwrapApiData(data, "Impossible de supprimer l'adresse");
};

export const setDefaultAddress = async (id: number): Promise<void> => {
  const { data } = await httpClient.put<ApiResponse<{ message: string }>>(
    `/api/addresses/${id}/default`,
  );
  unwrapApiData(data, "Impossible de définir l'adresse par défaut");
};
