import { httpClient } from '@/shared/lib/httpClient';
import { API_BASE_URL } from '@/shared/config/appConfig';
import { toSafeAttachmentUrl } from '@/shared/lib/externalUrls';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import type { ApiResponse } from '@/shared/types/api';
import type { BetaCampaignStatus, BugReportStatus } from '@/shared/contracts/statuses';

export interface BetaCampaign { id:number; name:string; description:string; status:BetaCampaignStatus; startsAt?:string|null; endsAt?:string|null; }
export interface PaginationMeta { page:number; perPage:number; total:number; totalPages:number; }
export interface BugReport { id:number; title:string; description:string; expectedBehavior?:string|null; actualBehavior?:string|null; severity:string; status:BugReportStatus; pageUrl?:string|null; campaignId?:number|null; campaign?:string|null; assignedTo?:{id:number;name:string;email:string}|null; duplicateOf?:{id:number;title:string}|null; duplicateReason?:string|null; attachments:string[]; attachmentUrls:string[]; createdAt:string; updatedAt?:string; lastAdminReplyAt?:string|null; lastReporterReplyAt?:string|null; }
export type BetaChoice = { value: string; label: string };
export type BetaProfileChoices = Record<string, BetaChoice[]>;
export interface BetaProfileDto {
  status?: string | null;
  motivation?: string | null;
  testingExperience?: string[];
  bugDescriptionAbility?: string[];
  technicalKnowledge?: string[];
  availability?: string[];
  accessibilityNeed?: string | null;
  assistiveTools?: string[];
  devices?: string[];
  browsers?: string[];
  testingTypes?: string[];
  betaConsent?: boolean;
}
const unwrap = <T>(response: ApiResponse<T>) =>
  unwrapApiData(response, response.message || 'Réponse API invalide.');
export const resolveBetaAttachmentUrl = (url:string) => toSafeAttachmentUrl(url, API_BASE_URL);
export const fetchBetaProfileChoices = async () => unwrap((await httpClient.get<ApiResponse<{choices: BetaProfileChoices}>>('/api/public/beta/profile-options')).data).choices;
export const fetchMyBetaProfile = async () => unwrap((await httpClient.get<ApiResponse<{profile: BetaProfileDto | null}>>('/api/beta/profile')).data).profile;
export const fetchBetaCampaigns = async () => unwrap((await httpClient.get<ApiResponse<{items:BetaCampaign[]}>>('/api/beta/campaigns')).data).items;
export const fetchMyBugReports = async (params:{page?:number;perPage?:number}={}) => unwrap((await httpClient.get<ApiResponse<{items:BugReport[];meta:PaginationMeta}>>('/api/beta/reports',{params})).data);
export const fetchMyBugReport = async (id:number) => unwrap((await httpClient.get<ApiResponse<{report:BugReport}>>(`/api/beta/reports/${id}`)).data).report;
export const updateMyBetaProfile = async (payload: BetaProfileDto) => unwrap((await httpClient.put<ApiResponse<BetaProfileDto>>('/api/beta/profile', payload)).data);
export const deleteMyBetaProfile = async () => unwrap((await httpClient.delete<ApiResponse<Record<string, never>>>('/api/beta/profile')).data);
export const createBugReport = async (payload: {title:string;description:string;expectedBehavior?:string;actualBehavior?:string;severity:string;campaignId?:number;pageUrl?:string;screenshots?:File[]}) => { const data=new FormData(); Object.entries(payload).forEach(([key,value])=>{if(key!=='screenshots'&&value!==undefined)data.append(key,String(value));}); payload.screenshots?.forEach(file=>data.append('screenshots[]',file)); return unwrap((await httpClient.post<ApiResponse<{id:number}>>('/api/beta/reports', data)).data); };

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

export const fetchBugReportComments = async (id: number, page = 1) => {
  return unwrap((await httpClient.get<ApiResponse<{ items: BugReportComment[]; meta: PaginationMeta }>>(`/api/beta/reports/${id}/comments`, { params: { page, perPage: 10 } })).data);
};

export const createBugReportComment = async (id: number, content: string) => {
  return unwrap((await httpClient.post<ApiResponse<BugReportComment>>(`/api/beta/reports/${id}/comments`, { content })).data);
};
