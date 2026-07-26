import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiMutationResult, type ApiResponse } from '@/shared/types/api';

export type PromotionAudienceDefinition = {
  label: string;
  description: string;
  defaults: Record<string, string | number | boolean>;
};

export type PromotionDto = {
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

export type Promotion = PromotionDto;

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

export const fetchPromotionAudiences = async (): Promise<
  Record<string, PromotionAudienceDefinition>
> => {
  const { data } = await httpClient.get<{
    data: { items: Record<string, PromotionAudienceDefinition> };
  }>('/api/admin/promotions/audiences');
  return data.data.items;
};

export const fetchPromotions = async (): Promise<PromotionDto[]> => {
  const { data } = await httpClient.get<{ data: { items: PromotionDto[] } }>(
    '/api/admin/promotions',
  );
  return data.data.items;
};

export const fetchPromotion = async (promotionId: number): Promise<PromotionDto> => {
  const { data } = await httpClient.get<{ data: { promotion: PromotionDto } }>(
    `/api/admin/promotions/${promotionId}`,
  );
  return data.data.promotion;
};

export const createPromotion = async (payload: PromotionPayload): Promise<ApiMutationResult<PromotionDto>> => {
  const { data } = await httpClient.post<ApiResponse<{ promotion: PromotionDto }>>(
    '/api/admin/promotions',
    payload,
  );
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data.promotion, message: data.message };
};

export const updatePromotion = async (
  promotionId: number,
  payload: PromotionPayload,
): Promise<ApiMutationResult<PromotionDto>> => {
  const { data } = await httpClient.put<ApiResponse<{ promotion: PromotionDto }>>(
    `/api/admin/promotions/${promotionId}`,
    payload,
  );
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data.promotion, message: data.message };
};

export const deletePromotion = async (promotionId: number): Promise<ApiMutationResult<{ deleted: boolean }>> => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(`/api/admin/promotions/${promotionId}`);
  if (!isApiOk(data)) throw new Error('Réponse API invalide.');
  return { data: data.data, message: data.message };
};
