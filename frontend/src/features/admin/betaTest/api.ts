import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
export interface AdminBetaTesterDto { id:number; userId:number; firstName:string; lastName:string; email:string; status:string; accessibilityNeed:string; availability:string[]; devices:string[]; browsers:string[]; testingTypes:string[]; assistiveTools:string[]; motivation:string; testingExperience:string; bugDescriptionAbility:string; technicalKnowledge?:string|null; createdAt:string; }
export interface AdminCampaignDto { id:number; name:string; description:string; status:string; createdAt:string; }
export interface AdminBugReportDto { id:number; title:string; description:string; severity:string; status:string; reporter:string; campaign?:string|null; createdAt:string; }
const unwrap = <T>(response:ApiResponse<T>) => { if (!isApiOk(response)) throw new Error(response.message); return response.data; };
export const fetchAdminBetaTesters = async (query='') => unwrap((await httpClient.get<ApiResponse<{items:AdminBetaTesterDto[]}>>(`/api/admin/beta-testers?perPage=100${query}`)).data).items;
export const fetchAdminCampaigns = async () => unwrap((await httpClient.get<ApiResponse<{items:AdminCampaignDto[]}>>('/api/admin/beta-campaigns')).data).items;
export const fetchAdminBugReports = async () => unwrap((await httpClient.get<ApiResponse<{items:AdminBugReportDto[]}>>('/api/admin/beta-reports')).data).items;
export const exportAdminBetaTesters = async () => { const response=await httpClient.get('/api/admin/beta-testers/export',{responseType:'blob'}); const url=URL.createObjectURL(response.data); const link=document.createElement('a'); link.href=url; link.download='beta-testeurs.csv'; link.click(); URL.revokeObjectURL(url); };
export const updateAdminBetaTester = async (id:number, status:string) => { await httpClient.patch(`/api/admin/beta-testers/${id}`, { status }); };
export const deleteAdminBetaTester = async (id:number) => { await httpClient.delete(`/api/admin/beta-testers/${id}`); };
export const createAdminCampaign = async (payload:{name:string;description:string;status:string}) => { await httpClient.post('/api/admin/beta-campaigns', payload); };

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

export const updateAdminBugReportStatus = async (id: number, status: string) => {
  return unwrap((await httpClient.patch<ApiResponse<{ id: number; status: string }>>(`/api/admin/beta-reports/${id}/status`, { status })).data);
};

export const deleteAdminBugReport = async (id: number) => {
  return unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-reports/${id}`)).data);
};

export const fetchBugReportComments = async (id: number) => {
  return unwrap((await httpClient.get<ApiResponse<{ items: BugReportCommentDto[] }>>(`/api/beta/reports/${id}/comments`)).data).items;
};

export const createBugReportComment = async (id: number, content: string) => {
  return unwrap((await httpClient.post<ApiResponse<BugReportCommentDto>>(`/api/beta/reports/${id}/comments`, { content })).data);
};

export const updateAdminCampaign = async (id: number, payload: { name?: string; description?: string; status?: string }) => {
  return unwrap((await httpClient.patch<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-campaigns/${id}`, payload)).data);
};

export const deleteAdminCampaign = async (id: number) => {
  return unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>(`/api/admin/beta-campaigns/${id}`)).data);
};
