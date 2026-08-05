import axios from 'axios';

import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { AdminPaymentDto, OrderDto } from '@/features/orders/api';

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
  if (isApiOk(data)) {
    return data.data as AdminDashboardDto;
  }

  const message = data.status === 'error' ? data.message : 'Impossible de charger le dashboard';
  throw new Error(message);
};

export const fetchAdminCustomers = async (
  search = '',
  sort:
    | 'recent_order'
    | 'highest_spent'
    | 'most_orders'
    | 'newest_account'
    | 'name_asc' = 'recent_order',
): Promise<AdminCustomerSummaryDto[]> => {
  const query = new URLSearchParams();
  if (search.trim() !== '') {
    query.set('search', search.trim());
  }
  query.set('sort', sort);

  const { data } = await httpClient.get<ApiResponse<{ items: AdminCustomerSummaryDto[] }>>(
    `/api/admin/customers?${query.toString()}`,
  );
  if (isApiOk(data)) {
    return data.data.items;
  }

  const message = data.status === 'error' ? data.message : 'Impossible de charger les clients';
  throw new Error(message);
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

  if (isApiOk(data)) {
    return {
      customer: data.data.customer,
      addresses: data.data.addresses,
      orders: data.data.orders,
      vouchers: data.data.vouchers,
    };
  }

  const message = data.status === 'error' ? data.message : 'Impossible de charger le client';
  throw new Error(message);
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

  if (isApiOk(data)) {
    return {
      adminNotes: data.data?.customer?.adminNotes ?? null,
      adminTags: data.data.customer.adminTags,
    };
  }

  const message =
    data.status === 'error' ? data.message : 'Impossible de mettre à jour le suivi interne';
  throw new Error(message);
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

  if (isApiOk(data)) {
    return {
      voucher: data.data?.voucher as AdminCustomerVoucherDto,
      emailSent: Boolean(data.data?.emailSent),
    };
  }

  const message =
    data.status === 'error' ? data.message : 'Impossible de créer le bon de réduction';
  throw new Error(message);
};

export const sendCustomerEmail = async (
  customerId: number,
  payload: AdminCustomerEmailPayload,
): Promise<{ message?: string }> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ sent: boolean }>>(
      `/api/admin/customers/${customerId}/send-email`,
      payload,
    );

    if (isApiOk(data)) {
      return { message: data.message ?? undefined };
    }

    const message = data.status === 'error' ? data.message : 'Impossible d’envoyer l’email';
    throw new Error(message);
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as ApiResponse<unknown> | undefined;
      const message =
        data?.status === 'error' && data.message ? data.message : 'Impossible d’envoyer l’email';
      throw new Error(message);
    }

    throw error instanceof Error ? error : new Error('Impossible d’envoyer l’email');
  }
};
