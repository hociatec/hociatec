import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse } from '@/shared/types/api';
import type { RentalItemDto, RentalListDto } from '../types/rentals';
import {
  requireArray,
  requireNumber,
  requireRecord,
  requireString,
  optionalString,
} from '@/shared/lib/contractValidation';

const parseRental = (value: unknown): RentalItemDto => {
  const item = requireRecord(value);
  const request = requireRecord(item.request);

  return {
    orderItemId: requireNumber(item.orderItemId),
    orderId: item.orderId === null ? null : (typeof item.orderId === 'number' ? item.orderId : null),
    orderNumber: optionalString(item.orderNumber) ?? null,
    productId: item.productId === null ? null : (typeof item.productId === 'number' ? item.productId : null),
    productName: requireString(item.productName),
    productSku: requireString(item.productSku),
    quantity: requireNumber(item.quantity),
    unitPriceCents: requireNumber(item.unitPriceCents),
    linePriceCents: requireNumber(item.linePriceCents),
    rentalMonths: typeof item.rentalMonths === 'number' ? item.rentalMonths : null,
    startDate: optionalString(item.startDate) ?? null,
    endDate: optionalString(item.endDate) ?? null,
    timelineStatus: requireString(item.timelineStatus),
    timelineStatusLabel: requireString(item.timelineStatusLabel),
    request: {
      status: requireString(request.status),
      type: optionalString(request.type) ?? null,
      requestedEndDate: optionalString(request.requestedEndDate) ?? null,
      createdAt: optionalString(request.createdAt) ?? null,
    },
  };
};

export const fetchMyRentals = async (): Promise<RentalListDto> => {
  const payload = unwrapApiData(
    (await httpClient.get<ApiResponse<{ upcoming: unknown[]; past: unknown[]; meta?: RentalListDto['meta'] }>>('/api/rentals/me')).data,
    'Impossible de charger vos locations.',
  );

  const response: RentalListDto = {
    upcoming: requireArray(payload.upcoming).map(parseRental),
    past: requireArray(payload.past).map(parseRental),
  };

  if (payload.meta) {
    response.meta = payload.meta;
  }

  return response;
};

export const requestRentalChange = async (
  orderItemId: number,
  payload: { action: 'extend' | 'end_early'; requestedEndDate: string },
): Promise<RentalItemDto> => {
  const data = unwrapApiData(
    (await httpClient.patch<ApiResponse<{ rental: unknown }>>(`/api/rentals/${orderItemId}/request`, payload)).data,
    'Impossible de mettre à jour la location.',
  );

  return parseRental(data.rental);
};
