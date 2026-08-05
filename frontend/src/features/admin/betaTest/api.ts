import { httpClient } from '@/shared/lib/httpClient';
import { API_BASE_URL } from '@/shared/config/appConfig';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
import type { BetaCampaignStatus, BugReportStatus } from '@/shared/contracts/statuses';
export interface AdminBetaTesterDto { id:number; userId:number; firstName:string; lastName:string; email:string; status:string; accessibilityNeed:string; availability:string[]; devices:string[]; browsers:string[]; testingTypes:string[]; assistiveTools:string[]; motivation:string; testingExperience:string[]; bugDescriptionAbility:string[]; technicalKnowledge:string[]; createdAt:string; }
export interface PaginationMeta { page:number; perPage:number; total:number; totalPages:number; }
export interface BetaAdminUserDto { id:number; name:string; email:string; }
export interface AdminBugReportDto { id:number; title:string; description:string; expectedBehavior?:string|null; actualBehavior?:string|null; severity:string; status:BugReportStatus; pageUrl?:string|null; reporter:string; reporterId?:number; reporterName?:string; campaignId?:number|null; campaign?:string|null; assignedTo?:BetaAdminUserDto|null; duplicateOf?:{id:number;title:string}|null; duplicateReason?:string|null; attachments:string[]; attachmentUrls:string[]; createdAt:string; updatedAt?:string; lastAdminReplyAt?:string|null; lastReporterReplyAt?:string|null; }
export interface AdminCampaignDto { id:number; name:string; description:string; status:BetaCampaignStatus; startsAt?:string|null; endsAt?:string|null; createdAt:string; enrolledCount?:number; reportCount?:number; reports?:AdminBugReportDto[]; }
export interface AdminBugReportActivityDto { id:number; action:string; fromValue?:string|null; toValue?:string|null; message?:string|null; createdAt:string; actor?:BetaAdminUserDto|null; }
export interface AdminBugReportDashboardDto { stats:{openReports:number;criticalOrHigh:number;awaitingAdminReply:number;awaitingUserReply:number;recentFixed:number;activeCampaigns:number}; admins:BetaAdminUserDto[]; }
const unwrap = <T>(response:ApiResponse<T>) => { if (!isApiOk(response)) throw new Error(response.message); return response.data; };
export const resolveBetaAttachmentUrl = (url:string) => new URL(url, API_BASE_URL).toString();
export const fetchAdminBetaTesters = async (query='') => unwrap((await httpClient.get<ApiResponse<{items:AdminBetaTesterDto[]}>>(`/api/admin/beta-testers?perPage=100${query}`)).data).items;
export const fetchAdminCampaigns = async () => unwrap((await httpClient.get<ApiResponse<{items:AdminCampaignDto[]}>>('/api/admin/beta-campaigns')).data).items;
export const fetchAdminBugReports = async (params:{page?:number;perPage?:number;status?:string;severity?:string;search?:string;assignedTo?:number|string;campaignId?:number|string}={}) => unwrap((await httpClient.get<ApiResponse<{items:AdminBugReportDto[];meta:PaginationMeta}>>('/api/admin/beta-reports',{params})).data);
export const fetchAdminBugReport = async (id:number) => unwrap((await httpClient.get<ApiResponse<{report:AdminBugReportDto}>>(`/api/beta/reports/${id}`)).data).report;
export const fetchAdminBugReportDashboard = async () => unwrap((await httpClient.get<ApiResponse<AdminBugReportDashboardDto>>('/api/admin/beta-reports/dashboard')).data);
export const exportAdminBetaTesters = async () => { const response=await httpClient.get('/api/admin/beta-testers/export',{responseType:'blob'}); const url=URL.createObjectURL(response.data); const link=document.createElement('a'); link.href=url; link.download='beta-testeurs.csv'; link.click(); URL.revokeObjectURL(url); };
export const exportAdminBugReports = async (params:{status?:string;severity?:string;search?:string;assignedTo?:number|string;campaignId?:number|string}={}) => { const response=await httpClient.get('/api/admin/beta-reports/export',{params,responseType:'blob'}); const url=URL.createObjectURL(response.data); const link=document.createElement('a'); link.href=url; link.download='signalements-beta.csv'; link.click(); URL.revokeObjectURL(url); };
export const updateAdminBetaTester = async (id:number, status:string) => { await httpClient.patch(`/api/admin/beta-testers/${id}`, { status }); };
export const deleteAdminBetaTester = async (id:number) => { await httpClient.delete(`/api/admin/beta-testers/${id}`); };
export const createAdminCampaign = async (payload:{name:string;description:string;status:BetaCampaignStatus;startsAt?:string;endsAt?:string}) => { await httpClient.post('/api/admin/beta-campaigns', payload); };

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

export const updateAdminBugReportStatus = async (id: number, status: BugReportStatus) => {
  return unwrap((await httpClient.patch<ApiResponse<{ id: number; status: BugReportStatus }>>(`/api/admin/beta-reports/${id}/status`, { status })).data);
};

export const assignAdminBugReport = async (id: number, assignedToId?: number | null) => {
  return unwrap((await httpClient.patch<ApiResponse<{ id: number }>>(`/api/admin/beta-reports/${id}/assignment`, { assignedToId })).data);
};

export const markAdminBugReportDuplicate = async (id: number, duplicateOfId: number, reason?: string) => {
  return unwrap((await httpClient.patch<ApiResponse<{ id: number }>>(`/api/admin/beta-reports/${id}/duplicate`, { duplicateOfId, reason })).data);
};

export const deleteAdminBugReport = async (id: number) => {
  return unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-reports/${id}`)).data);
};

export const fetchBugReportComments = async (id: number, page = 1) => {
  return unwrap((await httpClient.get<ApiResponse<{ items: BugReportCommentDto[]; meta: PaginationMeta }>>(`/api/beta/reports/${id}/comments`, { params: { page, perPage: 6 } })).data);
};

export const fetchBugReportActivity = async (id: number) => {
  return unwrap((await httpClient.get<ApiResponse<{ items: AdminBugReportActivityDto[] }>>(`/api/admin/beta-reports/${id}/activity`)).data).items;
};

export const createBugReportComment = async (id: number, content: string) => {
  return unwrap((await httpClient.post<ApiResponse<BugReportCommentDto>>(`/api/beta/reports/${id}/comments`, { content })).data);
};

export const updateAdminCampaign = async (id: number, payload: { name?: string; description?: string; status?: BetaCampaignStatus; startsAt?:string; endsAt?:string }) => {
  return unwrap((await httpClient.patch<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-campaigns/${id}`, payload)).data);
};

export const deleteAdminCampaign = async (id: number) => {
  return unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-campaigns/${id}`)).data);
};
