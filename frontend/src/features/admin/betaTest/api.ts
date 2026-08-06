import { httpClient } from '@/shared/lib/httpClient';
import { API_BASE_URL } from '@/shared/config/appConfig';
import { downloadCsvBlob } from '@/shared/lib/downloadFile';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import { toSafeAttachmentUrl } from '@/shared/lib/externalUrls';
import type { ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';
import type { BetaCampaignStatus, BugReportStatus } from '@/shared/contracts/statuses';

export interface AdminBetaTesterDto {
  id: number;
  userId: number;
  firstName: string;
  lastName: string;
  email: string;
  status: string;
  accessibilityNeed: string;
  availability: string[];
  devices: string[];
  browsers: string[];
  testingTypes: string[];
  assistiveTools: string[];
  motivation: string;
  testingExperience: string[];
  bugDescriptionAbility: string[];
  technicalKnowledge: string[];
  createdAt: string;
}

export interface BetaAdminUserDto {
  id: number;
  name: string;
  email: string;
}

export interface AdminBugReportDto {
  id: number;
  title: string;
  description: string;
  expectedBehavior?: string | null;
  actualBehavior?: string | null;
  severity: string;
  status: BugReportStatus;
  pageUrl?: string | null;
  reporter: string;
  reporterId?: number;
  reporterName?: string;
  campaignId?: number | null;
  campaign?: string | null;
  assignedTo?: BetaAdminUserDto | null;
  duplicateOf?: { id: number; title: string } | null;
  duplicateReason?: string | null;
  attachments: string[];
  attachmentUrls: string[];
  createdAt: string;
  updatedAt?: string;
  lastAdminReplyAt?: string | null;
  lastReporterReplyAt?: string | null;
}

export interface AdminCampaignDto {
  id: number;
  name: string;
  description: string;
  status: BetaCampaignStatus;
  startsAt?: string | null;
  endsAt?: string | null;
  createdAt: string;
  enrolledCount?: number;
  reportCount?: number;
  reports?: AdminBugReportDto[];
}

export interface AdminBugReportActivityDto {
  id: number;
  action: string;
  fromValue?: string | null;
  toValue?: string | null;
  message?: string | null;
  createdAt: string;
  actor?: BetaAdminUserDto | null;
}

export interface AdminBugReportDashboardDto {
  stats: {
    openReports: number;
    criticalOrHigh: number;
    awaitingAdminReply: number;
    awaitingUserReply: number;
    recentFixed: number;
    activeCampaigns: number;
  };
  admins: BetaAdminUserDto[];
}

export const resolveBetaAttachmentUrl = (url: string) => toSafeAttachmentUrl(url, API_BASE_URL);

export const fetchAdminBetaTesters = async (params: {
  page?: number;
  perPage?: number;
  search?: string;
  status?: string;
} = {}): Promise<PaginatedResult<AdminBetaTesterDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AdminBetaTesterDto[]; meta: PaginationMeta }>>(
    '/api/admin/beta-testers',
    { params: { perPage: 10, ...params } },
  );
  const payload = unwrapApiData(data, 'Réponse API invalide.');
  return { items: payload.items, meta: payload.meta };
};

export const fetchAdminCampaigns = async (
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<AdminCampaignDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AdminCampaignDto[]; meta: PaginationMeta }>>(
    '/api/admin/beta-campaigns',
    { params: { page, perPage } },
  );
  const payload = unwrapApiData(data, 'Réponse API invalide.');
  return { items: payload.items, meta: payload.meta };
};

export const fetchAdminBugReports = async (params: {
  page?: number;
  perPage?: number;
  status?: string;
  severity?: string;
  search?: string;
  assignedTo?: number | string;
  campaignId?: number | string;
} = {}): Promise<PaginatedResult<AdminBugReportDto>> => {
  const { data } = await httpClient.get<ApiResponse<{ items: AdminBugReportDto[]; meta: PaginationMeta }>>(
    '/api/admin/beta-reports',
    { params },
  );
  const payload = unwrapApiData(data, 'Réponse API invalide.');
  return { items: payload.items, meta: payload.meta };
};

export const fetchAdminBugReport = async (id: number) =>
  unwrapApiData(
    (await httpClient.get<ApiResponse<{ report: AdminBugReportDto }>>(`/api/beta/reports/${id}`)).data,
    'Réponse API invalide.',
  ).report;

export const fetchAdminBugReportDashboard = async () =>
  unwrapApiData(
    (await httpClient.get<ApiResponse<AdminBugReportDashboardDto>>('/api/admin/beta-reports/dashboard'))
      .data,
    'Réponse API invalide.',
  );

export const exportAdminBetaTesters = async () => {
  const response = await httpClient.get<Blob>('/api/admin/beta-testers/export', {
    responseType: 'blob',
  });
  await downloadCsvBlob(response.data, 'beta-testeurs.csv');
};

export const exportAdminBugReports = async (params: {
  status?: string;
  severity?: string;
  search?: string;
  assignedTo?: number | string;
  campaignId?: number | string;
} = {}) => {
  const response = await httpClient.get<Blob>('/api/admin/beta-reports/export', {
    params,
    responseType: 'blob',
  });
  await downloadCsvBlob(response.data, 'signalements-beta.csv');
};

export const updateAdminBetaTester = async (id: number, status: string) => {
  await httpClient.patch(`/api/admin/beta-testers/${id}`, { status });
};

export const deleteAdminBetaTester = async (id: number) => {
  await httpClient.delete(`/api/admin/beta-testers/${id}`);
};

export const createAdminCampaign = async (payload: {
  name: string;
  description: string;
  status: BetaCampaignStatus;
  startsAt?: string;
  endsAt?: string;
}) => {
  await httpClient.post('/api/admin/beta-campaigns', payload);
};

export interface BugReportCommentDto {
  id: number;
  content: string;
  createdAt: string;
  author: {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
    role: 'admin' | 'user';
  };
}

export const updateAdminBugReportStatus = async (id: number, status: BugReportStatus) =>
  unwrapApiData(
    (
      await httpClient.patch<ApiResponse<{ id: number; status: BugReportStatus }>>(
        `/api/admin/beta-reports/${id}/status`,
        { status },
      )
    ).data,
    'Réponse API invalide.',
  );

export const assignAdminBugReport = async (id: number, assignedToId?: number | null) =>
  unwrapApiData(
    (
      await httpClient.patch<ApiResponse<{ id: number }>>(
        `/api/admin/beta-reports/${id}/assignment`,
        { assignedToId },
      )
    ).data,
    'Réponse API invalide.',
  );

export const markAdminBugReportDuplicate = async (
  id: number,
  duplicateOfId: number,
  reason?: string,
) =>
  unwrapApiData(
    (
      await httpClient.patch<ApiResponse<{ id: number }>>(
        `/api/admin/beta-reports/${id}/duplicate`,
        { duplicateOfId, reason },
      )
    ).data,
    'Réponse API invalide.',
  );

export const deleteAdminBugReport = async (id: number) =>
  unwrapApiData(
    (await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-reports/${id}`)).data,
    'Réponse API invalide.',
  );

export const fetchBugReportComments = async (id: number, page = 1) =>
  unwrapApiData(
    (
      await httpClient.get<ApiResponse<{ items: BugReportCommentDto[]; meta: PaginationMeta }>>(
        `/api/beta/reports/${id}/comments`,
        { params: { page, perPage: 10 } },
      )
    ).data,
    'Réponse API invalide.',
  );

export const fetchBugReportActivity = async (id: number, page = 1) =>
  unwrapApiData(
    (
      await httpClient.get<ApiResponse<{ items: AdminBugReportActivityDto[]; meta: PaginationMeta }>>(
        `/api/admin/beta-reports/${id}/activity`,
        { params: { page, perPage: 10 } },
      )
    ).data,
    'Réponse API invalide.',
  );

export type { PaginationMeta };

export const createBugReportComment = async (id: number, content: string) =>
  unwrapApiData(
    (
      await httpClient.post<ApiResponse<BugReportCommentDto>>(`/api/beta/reports/${id}/comments`, {
        content,
      })
    ).data,
    'Réponse API invalide.',
  );

export const updateAdminCampaign = async (
  id: number,
  payload: {
    name?: string;
    description?: string;
    status?: BetaCampaignStatus;
    startsAt?: string;
    endsAt?: string;
  },
) =>
  unwrapApiData(
    (
      await httpClient.patch<ApiResponse<Record<string, unknown>>>(
        `/api/admin/beta-campaigns/${id}`,
        payload,
      )
    ).data,
    'Réponse API invalide.',
  );

export const deleteAdminCampaign = async (id: number) =>
  unwrapApiData(
    (await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-campaigns/${id}`)).data,
    'Réponse API invalide.',
  );
