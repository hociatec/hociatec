import { httpClient } from '@/shared/lib/httpClient';
import { API_BASE_URL } from '@/shared/config/appConfig';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
export interface BetaCampaign { id:number; name:string; description:string; status:string; startsAt?:string|null; endsAt?:string|null; }
export interface BugReport { id:number; title:string; description:string; expectedBehavior?:string|null; actualBehavior?:string|null; severity:string; status:string; pageUrl?:string|null; campaignId?:number|null; campaign?:string|null; attachments:string[]; attachmentUrls:string[]; createdAt:string; }
export type BetaChoice = { value: string; label: string };
export type BetaProfileChoices = Record<string, BetaChoice[]>;
const unwrap = <T>(response: ApiResponse<T>) => { if (!isApiOk(response)) throw new Error(response.message); return response.data; };
export const resolveBetaAttachmentUrl = (url:string) => new URL(url, API_BASE_URL).toString();
export const fetchBetaProfileChoices = async () => unwrap((await httpClient.get<ApiResponse<{choices: BetaProfileChoices}>>('/api/public/beta/profile-options')).data).choices;
export const fetchMyBetaProfile = async () => unwrap((await httpClient.get<ApiResponse<{profile: Record<string, unknown>}>>('/api/beta/profile')).data).profile;
export const fetchBetaCampaigns = async () => unwrap((await httpClient.get<ApiResponse<{items:BetaCampaign[]}>>('/api/beta/campaigns')).data).items;
export const fetchMyBugReports = async () => unwrap((await httpClient.get<ApiResponse<{items:BugReport[]}>>('/api/beta/reports')).data).items;
export const updateMyBetaProfile = async (payload: Record<string, unknown>) => unwrap((await httpClient.put<ApiResponse<Record<string, unknown>>>('/api/beta/profile', payload)).data);
export const leaveBetaProgram = async () => unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>('/api/beta/profile')).data);
export const createBugReport = async (payload: {title:string;description:string;expectedBehavior?:string;actualBehavior?:string;severity:string;campaignId?:number;pageUrl?:string;screenshots?:File[]}) => { const data=new FormData(); Object.entries(payload).forEach(([key,value])=>{if(key!=='screenshots'&&value!==undefined)data.append(key,String(value));}); payload.screenshots?.forEach(file=>data.append('screenshots[]',file)); return unwrap((await httpClient.post<ApiResponse<{id:number}>>('/api/beta/reports', data, {headers:{'Content-Type':'multipart/form-data'}})).data); };

export interface BugReportComment {
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

export const fetchBugReportComments = async (id: number) => {
  return unwrap((await httpClient.get<ApiResponse<{ items: BugReportComment[] }>>(`/api/beta/reports/${id}/comments`)).data).items;
};

export const createBugReportComment = async (id: number, content: string) => {
  return unwrap((await httpClient.post<ApiResponse<BugReportComment>>(`/api/beta/reports/${id}/comments`, { content })).data);
};
