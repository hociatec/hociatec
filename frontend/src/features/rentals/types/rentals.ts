export interface RentalRequestDto {
  status: 'none' | 'pending' | 'pending_payment' | string;
  type?: 'extend' | 'end_early' | string | null;
  requestedEndDate?: string | null;
  createdAt?: string | null;
}

export interface RentalExtensionDto {
  orderId?: number | null;
  sourceOrderItemId?: number | null;
  checkoutSessionId?: string | null;
  checkoutUrl?: string | null;
  checkoutStatus?: 'open' | 'paid' | 'failed' | 'expired' | string | null;
}

export interface RentalReturnPlanDto {
  status: 'none' | 'scheduled' | 'completed' | string;
  mode?: 'pickup_home' | 'dropoff_store' | string | null;
  requestedDate?: string | null;
  requestedAt?: string | null;
  completedAt?: string | null;
}

export interface RentalItemDto {
  orderItemId: number;
  orderId?: number | null;
  orderNumber?: string | null;
  productId?: number | null;
  productName: string;
  productSku: string;
  quantity: number;
  unitPriceCents: number;
  linePriceCents: number;
  rentalMonths?: number | null;
  startDate?: string | null;
  endDate?: string | null;
  timelineStatus: 'upcoming' | 'active' | 'past' | string;
  timelineStatusLabel: string;
  request: RentalRequestDto;
  extension: RentalExtensionDto;
  returnPlan: RentalReturnPlanDto;
}

export interface RentalListDto {
  upcoming: RentalItemDto[];
  past: RentalItemDto[];
  meta?: {
    page: number;
    perPage: number;
    upcomingTotal: number;
    pastTotal: number;
  };
}

export interface RentalCheckoutDto {
  mode: 'redirect' | string;
  orderId?: number | null;
  checkoutUrl?: string | null;
  checkoutSessionId?: string | null;
}

export interface RentalChangeResponseDto {
  rental: RentalItemDto;
  checkout?: RentalCheckoutDto | null;
}
