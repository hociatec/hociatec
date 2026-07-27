import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';
export interface BetaCampaign { id:number; name:string; description:string; status:string; startsAt?:string|null; endsAt?:string|null; }
export interface BugReport { id:number; title:string; description:string; severity:string; status:string; campaign?:string|null; attachments:string[]; createdAt:string; }
const unwrap = <T>(response: ApiResponse<T>) => { if (!isApiOk(response)) throw new Error(response.message); return response.data; };
export const fetchMyBetaProfile = async () => unwrap((await httpClient.get<ApiResponse<{profile: Record<string, unknown>}>>('/api/beta/profile')).data).profile;
export const fetchBetaCampaigns = async () => unwrap((await httpClient.get<ApiResponse<{items:BetaCampaign[]}>>('/api/beta/campaigns')).data).items;
export const fetchMyBugReports = async () => unwrap((await httpClient.get<ApiResponse<{items:BugReport[]}>>('/api/beta/reports')).data).items;
export const updateMyBetaProfile = async (payload: Record<string, unknown>) => unwrap((await httpClient.put<ApiResponse<Record<string, unknown>>>('/api/beta/profile', payload)).data);
export const leaveBetaProgram = async () => unwrap((await httpClient.delete<ApiResponse<Record<string, unknown>>>('/api/beta/profile')).data);
export const createBugReport = async (payload: {title:string;description:string;expectedBehavior?:string;actualBehavior?:string;severity:string;campaignId?:number;pageUrl?:string;screenshots?:File[]}) => { const data=new FormData(); Object.entries(payload).forEach(([key,value])=>{if(key!=='screenshots'&&value!==undefined)data.append(key,String(value));}); payload.screenshots?.forEach(file=>data.append('screenshots[]',file)); return unwrap((await httpClient.post<ApiResponse<{id:number}>>('/api/beta/reports', data, {headers:{'Content-Type':'multipart/form-data'}})).data); };
