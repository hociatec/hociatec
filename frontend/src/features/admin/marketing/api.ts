import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiMutationResult, type ApiResponse, type PaginatedResult, type PaginationMeta } from '@/shared/types/api';

export type MarketingSegmentDefinition = {
  label: string;
  description: string;
  defaults: Record<string, string | number | boolean>;
  type?: 'campaign' | 'transactional' | string;
};

export type MarketingTemplate = {
  id: number;
  name: string;
  slug: string;
  scenarioKey: string;
  subjectTemplate: string;
  htmlBody: string;
  previewHtmlBody: string;
  textBody: string | null;
  isActive: boolean;
  createdAt?: string;
  updatedAt?: string;
};

export type MarketingCampaign = {
  id: number;
  name: string;
  segmentKey: string;
  criteria: Record<string, string | number | boolean>;
  subjectSnapshot: string;
  recipientsCount: number;
  createdByEmail: string | null;
  sentAt: string;
  template: { id: number; name: string } | null;
};

export type MarketingAudiencePreview = {
  count: number;
  recipients: Array<{ id: number; email: string; fullName: string }>;
  description: string;
};

export type MarketingTemplatePayload = {
  name: string;
  slug: string;
  scenarioKey: string;
  subjectTemplate: string;
  htmlBody: string;
  textBody?: string | null;
  isActive: boolean;
};

export type MarketingCampaignPayload = {
  name: string;
  templateId?: number | null;
  segmentKey: string;
  criteria: Record<string, string | number | boolean>;
  subject: string;
  htmlBody: string;
  textBody?: string | null;
};

export const fetchMarketingSegments = async (
  type: 'templates' | 'campaigns' | 'transactional' = 'templates',
): Promise<Record<string, MarketingSegmentDefinition>> => {
  const { data } = await httpClient.get<{
    data: { items: Record<string, MarketingSegmentDefinition> };
  }>(`/api/admin/marketing/segments?type=${type}`);
  return data.data.items;
};

export const fetchMarketingTemplates = async (): Promise<MarketingTemplate[]> => {
  const { data } = await httpClient.get<{ data: { items: MarketingTemplate[] } }>(
    '/api/admin/marketing/templates',
    { params: { page: 1, perPage: 100 } },
  );
  return data.data.items;
};

export const fetchMarketingTemplatesPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<MarketingTemplate>> => {
  const { data } = await httpClient.get<{ data: { items: MarketingTemplate[]; meta: PaginationMeta } }>(
    '/api/admin/marketing/templates',
    { params: { page, perPage } },
  );
  return { items: data.data.items, meta: data.data.meta };
};

export const fetchMarketingTemplate = async (templateId: number): Promise<MarketingTemplate> => {
  const { data } = await httpClient.get<{ data: { template: MarketingTemplate } }>(
    `/api/admin/marketing/templates/${templateId}`,
  );
  return data.data.template;
};

export const createMarketingTemplate = async (
  payload: MarketingTemplatePayload,
): Promise<ApiMutationResult<MarketingTemplate>> => {
  const { data } = await httpClient.post<ApiResponse<{ template: MarketingTemplate }>>(
    '/api/admin/marketing/templates',
    payload,
  );
  if (!isApiOk(data)) throw new Error(extractApiErrorMessage(data, 'Réponse API invalide.'));
  return { data: data.data.template, message: data.message };
};

export const updateMarketingTemplate = async (
  templateId: number,
  payload: MarketingTemplatePayload,
): Promise<ApiMutationResult<MarketingTemplate>> => {
  const { data } = await httpClient.put<ApiResponse<{ template: MarketingTemplate }>>(
    `/api/admin/marketing/templates/${templateId}`,
    payload,
  );
  if (!isApiOk(data)) throw new Error(extractApiErrorMessage(data, 'Réponse API invalide.'));
  return { data: data.data.template, message: data.message };
};

export const deleteMarketingTemplate = async (templateId: number) => {
  const { data } = await httpClient.delete<ApiResponse<{ deleted: boolean }>>(`/api/admin/marketing/templates/${templateId}`);
  if (!isApiOk(data)) throw new Error(extractApiErrorMessage(data, 'Réponse API invalide.'));
  return { data: data.data, message: data.message };
};

export const fetchMarketingCampaignsPage = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<MarketingCampaign>> => {
  const { data } = await httpClient.get<{ data: { items: MarketingCampaign[]; meta: PaginationMeta } }>(
    '/api/admin/marketing/campaigns',
    { params: { page, perPage } },
  );
  return { items: data.data.items, meta: data.data.meta };
};

export const previewMarketingAudience = async (
  segmentKey: string,
  criteria: Record<string, string | number | boolean>,
): Promise<{
  preview: MarketingAudiencePreview;
  segments: Record<string, MarketingSegmentDefinition>;
}> => {
  const { data } = await httpClient.post<{
    data: {
      preview: MarketingAudiencePreview;
      segments: Record<string, MarketingSegmentDefinition>;
    };
  }>('/api/admin/marketing/campaigns/preview', { segmentKey, criteria });

  return data.data;
};

export const sendMarketingCampaign = async (
  payload: MarketingCampaignPayload,
): Promise<{ id: number; name: string; recipientsCount: number; sentAt: string }> => {
  const { data } = await httpClient.post<{
    data: {
      campaign: { id: number; name: string; recipientsCount: number; sentAt: string };
    };
  }>('/api/admin/marketing/campaigns/send', payload);

  return data.data.campaign;
};
