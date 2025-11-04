import { httpClient } from '@/shared/lib/httpClient';

export type QuoteStatus = 'draft' | 'sent' | 'accepted' | 'refused' | 'expired';

export interface QuoteItemInput {
  id?: number;
  type: 'service' | 'product' | 'custom';
  productId?: number | null;
  serviceId?: number | null;
  name: string;
  description?: string | null;
  unit?: string | null;
  quantity: number;
  unitPriceCents: number;
  vatRate: number; // percentage e.g. 20 for 20%
  discountCents?: number;
}

export interface QuoteInput {
  status?: QuoteStatus;
  customer?: {
    name?: string | null;
    email?: string | null;
    company?: string | null;
    address?: string | null;
  };
  items: QuoteItemInput[];
  discountCents?: number;
  shippingCents?: number;
  conditions?: string | null;
}

export const fetchAdminQuotes = async (params?: { q?: string; status?: string }) => {
  const res = await httpClient.get('/api/admin/quotes', { params });
  return (res.data?.data?.items ?? []) as any[];
};

export const fetchAdminQuote = async (id: number) => {
  const res = await httpClient.get(`/api/admin/quotes/${id}`);
  return res.data?.data as any;
};

export const createAdminQuote = async (payload: QuoteInput) => {
  const res = await httpClient.post('/api/admin/quotes', payload);
  return res.data?.data as any;
};

export const updateAdminQuote = async (id: number, payload: QuoteInput) => {
  const res = await httpClient.post(`/api/admin/quotes/${id}`, payload);
  return res.data?.data as any;
};

export const deleteAdminQuote = async (id: number) => {
  const res = await httpClient.delete(`/api/admin/quotes/${id}`);
  return res.data?.data as any;
};

export const duplicateAdminQuote = async (id: number) => {
  const res = await httpClient.post(`/api/admin/quotes/${id}/duplicate`);
  return res.data?.data as any;
};

export const generateAdminQuotePdf = async (id: number) => {
  const res = await httpClient.post(`/api/admin/quotes/${id}/pdf`, null, { responseType: 'blob' });
  return res.data as Blob;
};

export const sendAdminQuoteEmail = async (id: number, to?: string) => {
  const res = await httpClient.post(`/api/admin/quotes/${id}/send-email`, to ? { to } : {});
  return res.data?.data as any;
};

export const fetchAdminQuoteServices = async () => {
  const res = await httpClient.get('/api/admin/quotes/services');
  return (res.data?.data?.items ?? []) as any[];
};

export const createAdminQuoteService = async (payload: {
  title: string;
  description?: string;
  unit?: string;
  price: number; // euros
  vatRate: number; // percent
}) => {
  const form = new FormData();
  form.append('title', payload.title);
  if (payload.description) form.append('description', payload.description);
  if (payload.unit) form.append('unit', payload.unit);
  form.append('price', String(payload.price));
  form.append('vatRate', String(payload.vatRate));
  const res = await httpClient.post('/api/admin/quotes/services', form);
  return res.data?.data as any;
};

export const updateAdminQuoteService = async (
  id: number,
  payload: Partial<{ title: string; description: string; unit: string; price: number; vatRate: number }>,
) => {
  const form = new FormData();
  for (const [k, v] of Object.entries(payload)) {
    if (v !== undefined && v !== null) form.append(k, String(v));
  }
  const res = await httpClient.post(`/api/admin/quotes/services/${id}`, form);
  return res.data?.data as any;
};

export const deleteAdminQuoteService = async (id: number) => {
  const res = await httpClient.delete(`/api/admin/quotes/services/${id}`);
  return res.data?.data as any;
};

export const createPublicQuote = async (payload: QuoteInput) => {
  const res = await httpClient.post('/api/public/quotes', payload);
  return res.data?.data as any;
};

export const fetchPublicQuoteServices = async () => {
  const res = await httpClient.get('/api/public/quotes/services');
  return (res.data?.data?.items ?? []) as any[];
};

export const fetchMyQuotes = async () => {
  const res = await httpClient.get('/api/quotes/me');
  return (res.data?.data?.items ?? []) as any[];
};

export const deleteMyQuote = async (id: number) => {
  const res = await httpClient.delete(`/api/quotes/me/${id}`);
  return res.data?.data as any;
};
