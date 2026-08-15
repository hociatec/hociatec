export interface RentalRequestDto {
  status: 'none' | 'pending' | string;
  type?: 'extend' | 'end_early' | string | null;
  requestedEndDate?: string | null;
  createdAt?: string | null;
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
