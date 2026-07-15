import { httpClient } from '@/shared/lib/httpClient';

export type PromotionAudienceDefinition = {
  label: string;
  description: string;
  defaults: Record<string, string | number | boolean>;
};

export type Promotion = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  audienceKey: string;
  criteria: Record<string, string | number | boolean>;
  isActive: boolean;
  startsAt: string | null;
  endsAt: string | null;
  createdAt?: string;
  updatedAt?: string;
};

export type PromotionPayload = {
  name: string;
  slug: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  audienceKey: string;
  criteria: Record<string, string | number | boolean>;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
};

export const fetchPromotionAudiences = async (): Promise<Record<string, PromotionAudienceDefinition>> => {
  const { data } =
    await httpClient.get<{ data: { items: Record<string, PromotionAudienceDefinition> } }>(
      '/api/admin/promotions/audiences',
    );
  return data.data.items;
};

export const fetchPromotions = async (): Promise<Promotion[]> => {
  const { data } =
    await httpClient.get<{ data: { items: Promotion[] } }>(
      '/api/admin/promotions',
    );
  return data.data.items;
};

export const fetchPromotion = async (promotionId: number): Promise<Promotion> => {
  const { data } =
    await httpClient.get<{ data: { promotion: Promotion } }>(
      `/api/admin/promotions/${promotionId}`,
    );
  return data.data.promotion;
};

export const createPromotion = async (payload: PromotionPayload): Promise<Promotion> => {
  const { data } =
    await httpClient.post<{ data: { promotion: Promotion } }>(
      '/api/admin/promotions',
      payload,
    );
  return data.data.promotion;
};

export const updatePromotion = async (promotionId: number, payload: PromotionPayload): Promise<Promotion> => {
  const { data } =
    await httpClient.put<{ data: { promotion: Promotion } }>(
      `/api/admin/promotions/${promotionId}`,
      payload,
    );
  return data.data.promotion;
};

export const deletePromotion = async (promotionId: number): Promise<void> => {
  await httpClient.delete(`/api/admin/promotions/${promotionId}`);
};
