import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

export type AdminRentalAction = 'approve_extension' | 'approve_end_early' | 'reject_request' | 'mark_returned';

export type AdminRentalDto = {
  orderItemId: number;
  orderId: number | null;
  orderNumber: string | null;
  orderStatus: string | null;
  orderStatusLabel: string | null;
  productId: number | null;
  productName: string;
  productSku: string;
  quantity: number;
  unitPriceCents: number;
  linePriceCents: number;
  rentalMonths: number | null;
  startDate: string | null;
  endDate: string | null;
  timelineStatus: 'upcoming' | 'active' | 'past';
  timelineStatusLabel: string;
  request: {
    status: 'none' | 'pending' | 'pending_payment';
    type: 'extend' | 'end_early' | null;
    requestedEndDate: string | null;
    createdAt: string | null;
  };
  returnPlan: {
    status: 'none' | 'scheduled' | 'completed';
    mode: 'pickup_home' | 'dropoff_store' | null;
    requestedDate: string | null;
    requestedAt: string | null;
    completedAt: string | null;
  };
  customer: {
    id: number | null;
    email: string | null;
    firstName: string | null;
    lastName: string | null;
  };
  allowedAdminActions: AdminRentalAction[];
};

export const fetchAdminRentals = async (
  page = 1,
  perPage = 20,
  q = '',
  timeline = 'all',
  requestStatus = 'all',
  requestType = 'all',
): Promise<PaginatedResult<AdminRentalDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AdminRentalDto[]; meta: PaginationMeta }>>(
    '/api/admin/rentals',
    { params: { page, perPage, q, timeline, requestStatus, requestType } },
  );

  return unwrapApiData(data, 'Impossible de charger les locations.');
};

export const fetchAdminRental = async (rentalId: number): Promise<AdminRentalDto> => {
  const { data } = await httpClient.get<ApiResponse<{ item: AdminRentalDto }>>(`/api/admin/rentals/${rentalId}`);
  return unwrapApiData(data, 'Impossible de charger la location.').item;
};

export const updateAdminRental = async (
  rentalId: number,
  action: AdminRentalAction,
): Promise<ApiMutationResult<AdminRentalDto>> => {
  const { data } = await httpClient.patch<ApiResponse<{ item: AdminRentalDto }>>(
    `/api/admin/rentals/${rentalId}`,
    { action },
  );
  const payload = unwrapApiData(data, 'Impossible de mettre à jour la location.');

  return {
    data: payload.item,
    message: data.message,
  };
};
