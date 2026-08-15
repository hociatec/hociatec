import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse } from '@/shared/types/api';
import type { RentalChangeResponseDto, RentalItemDto, RentalListDto } from '../types/rentals';
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
    extension: {
      orderId: typeof item.extension === 'object' && item.extension && typeof (item.extension as Record<string, unknown>).orderId === 'number'
        ? ((item.extension as Record<string, unknown>).orderId as number)
        : null,
      sourceOrderItemId: typeof item.extension === 'object' && item.extension && typeof (item.extension as Record<string, unknown>).sourceOrderItemId === 'number'
        ? ((item.extension as Record<string, unknown>).sourceOrderItemId as number)
        : null,
    },
    returnPlan: {
      status: typeof item.returnPlan === 'object' && item.returnPlan
        ? requireString((item.returnPlan as Record<string, unknown>).status)
        : 'none',
      mode: typeof item.returnPlan === 'object' && item.returnPlan
        ? optionalString((item.returnPlan as Record<string, unknown>).mode) ?? null
        : null,
      requestedDate: typeof item.returnPlan === 'object' && item.returnPlan
        ? optionalString((item.returnPlan as Record<string, unknown>).requestedDate) ?? null
        : null,
      requestedAt: typeof item.returnPlan === 'object' && item.returnPlan
        ? optionalString((item.returnPlan as Record<string, unknown>).requestedAt) ?? null
        : null,
      completedAt: typeof item.returnPlan === 'object' && item.returnPlan
        ? optionalString((item.returnPlan as Record<string, unknown>).completedAt) ?? null
        : null,
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
  payload: { action: 'extend' | 'end_early'; requestedEndDate: string; clientPlatform?: 'web' | 'ios' },
): Promise<RentalChangeResponseDto> => {
  const data = unwrapApiData(
    (await httpClient.patch<ApiResponse<{ rental: unknown; checkout?: Record<string, unknown> | null }>>(`/api/rentals/${orderItemId}/request`, payload)).data,
    'Impossible de mettre à jour la location.',
  );

  return {
    rental: parseRental(data.rental),
    checkout: data.checkout
      ? {
          mode: typeof data.checkout.mode === 'string' ? data.checkout.mode : 'redirect',
          orderId: typeof data.checkout.orderId === 'number' ? data.checkout.orderId : null,
          checkoutUrl: optionalString(data.checkout.checkoutUrl) ?? null,
          checkoutSessionId: optionalString(data.checkout.checkoutSessionId) ?? null,
        }
      : null,
  };
};

export const planRentalReturn = async (
  orderItemId: number,
  payload: { mode: 'pickup_home' | 'dropoff_store'; requestedDate: string },
): Promise<RentalItemDto> => {
  const data = unwrapApiData(
    (await httpClient.put<ApiResponse<{ rental: unknown }>>(`/api/rentals/${orderItemId}/return-plan`, payload)).data,
    'Impossible de planifier la restitution.',
  );

  return parseRental(data.rental);
};
