import axios from 'axios';

import { httpClient } from '@/shared/lib/httpClient';
import { createApiError, unwrapApiData } from '@/shared/lib/responseHelpers';
import { type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';
import type { AdminPaymentDto, OrderDto } from '@/features/orders/publicApi';

export interface AdminCustomerSummaryDto {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phoneNumber: string;
  isVerified: boolean;
  adminTags: string[];
  createdAt: string;
  ordersCount: number;
  totalSpentCents: number;
  lastOrderAt?: string | null;
}

export interface AdminCustomerAddressDto {
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

export interface AdminCustomerDetailDto {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  fullName: string;
  phoneNumber: string;
  isVerified: boolean;
  adminNotes?: string | null;
  adminTags: string[];
  createdAt: string;
  ordersCount: number;
  totalSpentCents: number;
  lastOrderAt?: string | null;
  lastOrderNumber?: string | null;
}

export interface AdminCustomerVoucherDto {
  id: number;
  name: string;
  code: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
  recipientUserId?: number | null;
  recipientEmail?: string | null;
  sentAt?: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CustomerVoucherPayload {
  name: string;
  code?: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
  sendEmail: boolean;
}

export interface AdminCustomerEmailPayload {
  subject: string;
  message: string;
}

export interface AdminDashboardMetricDto {
  count: number;
  totalCents: number;
}

export interface AdminDashboardDto {
  metrics: {
    today: AdminDashboardMetricDto;
    week: AdminDashboardMetricDto;
    month: AdminDashboardMetricDto;
    statusCounts: Record<string, number>;
    issuesCount: number;
    lowStockCount: number;
    customersCount: number;
    supportOpenCount?: number;
    refundsPendingCount?: number;
  };
  notifications?: Array<{
    id: string;
    type: string;
    severity: 'action' | 'danger' | 'info' | string;
    title: string;
    message?: string | null;
    createdAt: string;
    to: string;
    resource?: {
      type: string;
      id?: number | null;
      number?: string | null;
    };
  }>;
  recentOrders: OrderDto[];
  recentEvents: Array<{
    id: number;
    type: string;
    message?: string | null;
    createdAt: string;
    order: {
      id: number;
      number: string;
    };
    actor?: {
      id?: number | null;
      name?: string | null;
    };
  }>;
  topCustomers: AdminCustomerSummaryDto[];
  payments: {
    statusCounts: Record<string, number>;
    paidWithoutOrderCount: number;
    recent: AdminPaymentDto[];
    attention: Array<AdminPaymentDto & { requiresAttention?: boolean }>;
  };
}

export const fetchAdminDashboard = async (): Promise<AdminDashboardDto> => {
  const { data } = await httpClient.get<ApiResponse<AdminDashboardDto>>('/api/admin/dashboard');
  return unwrapApiData(data, 'Impossible de charger le dashboard') as AdminDashboardDto;
};

export const fetchAdminCustomers = async (
  search = '',
  sort:
    | 'recent_order'
    | 'highest_spent'
    | 'most_orders'
    | 'newest_account'
    | 'name_asc' = 'recent_order',
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<AdminCustomerSummaryDto>> => {
  const query = new URLSearchParams();
  if (search.trim() !== '') {
    query.set('search', search.trim());
  }
  query.set('sort', sort);
  query.set('page', String(page));
  query.set('perPage', String(perPage));

  const { data } = await httpClient.get<ApiResponse<{ items: AdminCustomerSummaryDto[]; meta: PaginationMeta }>>(
    `/api/admin/customers?${query.toString()}`,
  );
  const payload = unwrapApiData(data, 'Impossible de charger les clients');
  return { items: payload.items, meta: payload.meta };
};

export const fetchAdminCustomerById = async (
  customerId: number,
): Promise<{
  customer: AdminCustomerDetailDto;
  addresses: AdminCustomerAddressDto[];
  orders: OrderDto[];
  vouchers: AdminCustomerVoucherDto[];
}> => {
  const { data } = await httpClient.get<
    ApiResponse<{
      customer: AdminCustomerDetailDto;
      addresses: AdminCustomerAddressDto[];
      orders: OrderDto[];
      vouchers: AdminCustomerVoucherDto[];
    }>
  >(`/api/admin/customers/${customerId}`);

  const payload = unwrapApiData(data, 'Impossible de charger le client');
  return {
    customer: payload.customer,
    addresses: payload.addresses,
    orders: payload.orders,
    vouchers: payload.vouchers,
  };
};

export const updateAdminCustomerAdminProfile = async (
  customerId: number,
  payload: { adminNotes: string; adminTags: string[] },
): Promise<{ adminNotes?: string | null; adminTags: string[] }> => {
  const { data } = await httpClient.patch<
    ApiResponse<{
      customer: {
        adminNotes?: string | null;
        adminTags: string[];
      };
    }>
  >(`/api/admin/customers/${customerId}/admin-profile`, payload);

  const responsePayload = unwrapApiData(data, 'Impossible de mettre à jour le suivi interne');
  return {
    adminNotes: responsePayload.customer?.adminNotes ?? null,
    adminTags: responsePayload.customer.adminTags,
  };
};

export const createCustomerVoucher = async (
  customerId: number,
  payload: CustomerVoucherPayload,
): Promise<{ voucher: AdminCustomerVoucherDto; emailSent: boolean }> => {
  const { data } = await httpClient.post<
    ApiResponse<{
      voucher: AdminCustomerVoucherDto;
      emailSent: boolean;
    }>
  >(`/api/admin/customers/${customerId}/vouchers`, payload);

  const responsePayload = unwrapApiData(data, 'Impossible de créer le bon de réduction');
  return {
    voucher: responsePayload.voucher,
    emailSent: responsePayload.emailSent,
  };
};

export const sendCustomerEmail = async (
  customerId: number,
  payload: AdminCustomerEmailPayload,
): Promise<{ message?: string | undefined }> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ sent: boolean }>>(
      `/api/admin/customers/${customerId}/send-email`,
      payload,
    );

    unwrapApiData(data, 'Impossible d’envoyer l’email');
    return { message: typeof data.message === 'string' && data.message.trim() !== '' ? data.message : undefined };
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as ApiResponse<unknown> | undefined;
      if (data) {
        throw createApiError(data, 'Impossible d’envoyer l’email');
      }
    }

    throw error instanceof Error ? error : new Error('Impossible d’envoyer l’email');
  }
};
