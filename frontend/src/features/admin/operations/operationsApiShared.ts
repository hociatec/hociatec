import axios from 'axios';

import { createApiError, unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse } from '@/shared/types/api';

export interface SupportRequestDto {
  id: number;
  status: string;
  statusLabel: string;
  reason: string;
  subject: string;
  message?: string | null;
  internalNotes?: string | null;
  customer: { id: number; name: string; email: string };
  order?: { id: number; number: string } | null;
  createdAt: string;
  updatedAt: string;
  resolvedAt?: string | null;
}

export interface FulfillmentOrderDto {
  id: number;
  number: string;
  status: string;
  statusLabel: string;
  customer: { id: number; name: string; email: string };
  totalPriceCents: number;
  shipping: {
    name?: string | null;
    address?: string | null;
    postalCode?: string | null;
    city?: string | null;
  };
  delivery: {
    status: string;
    statusLabel: string;
    carrier?: string | null;
    trackingNumber?: string | null;
    trackingUrl?: string | null;
  };
  items: Array<{ name: string; sku: string; quantity: number }>;
  createdAt: string;
}

export interface RefundRequestDto {
  id: number;
  order: { id: number; number: string };
  paymentId?: number | null;
  amountCents: number;
  currencyCode: string;
  status: string;
  reason?: string | null;
  internalNotes?: string | null;
  stripeRefundId?: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface StockMovementDto {
  id: number;
  product: { id: number; name: string; sku: string };
  delta: number;
  stockBefore: number;
  stockAfter: number;
  reason: string;
  note?: string | null;
  actor?: string | null;
  createdAt: string;
}

export interface EmailLogDto {
  type: string;
  scenario: string;
  scenarioLabel?: string;
  status: string;
  statusLabel?: string;
  recipient?: string | null;
  subject?: string | null;
  related?: { type: string; id: number; label: string };
  createdAt: string;
}

export interface OperationsOverviewDto {
  support: { openCount: number; items: SupportRequestDto[] };
  refunds: { pendingCount: number; items: RefundRequestDto[] };
  stock: {
    lowStockThreshold?: number;
    lowStockCount: number;
    lowStockItems?: Array<{
      id: number;
      name: string;
      sku: string;
      stock: number;
      lowStockThreshold?: number;
      category: string;
    }>;
    movements: StockMovementDto[];
  };
  emails: { items: EmailLogDto[] };
  actions: Array<{ label: string; href: string }>;
}

export const unwrap = <T>(data: ApiResponse<T>, fallback: string): T => {
  return unwrapApiData(data, fallback);
};

export const rethrowApiError = (error: unknown, fallback: string): never => {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiResponse<unknown> | undefined;
    if (data) {
      throw createApiError(data, fallback);
    }
  }

  throw new Error(error instanceof Error ? error.message : fallback);
};
