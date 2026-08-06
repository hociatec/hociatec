import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk } from '@/shared/types/api';
import type { ApiMutationResult, ApiResponse, PaginatedResult, PaginationMeta } from '@/shared/types/api';

export type AuditType = 'performance' | 'security' | 'ux' | 'seo' | 'technical' | 'accessibility';
export type AuditStatus = 'new' | 'in_progress' | 'review' | 'done';

export interface AuditMetadataOption {
  value: string;
  label: string;
}

export interface AuditMetadataDto {
  types: AuditMetadataOption[];
  statuses: AuditMetadataOption[];
}

export interface AuditListItemDto {
  id: number;
  number: string;
  type: AuditType;
  status: AuditStatus;
  typeLabel: string;
  statusLabel: string;
  url: string;
  createdAt: string;
}

export interface AuditItemDto {
  id: number;
  category: string;
  key: string;
  label: string;
  position: number;
  level?: string | null;
  isCompliant: boolean | null;
  comment: string | null;
}

export interface AuditDetailDto extends AuditListItemDto {
  objectives: string | null;
  items: AuditItemDto[];
}

export interface AuditEventDto {
  id: number;
  type: string;
  message: string | null;
  createdAt: string;
  actor?: { id: number | null; name: string | null };
}

export async function createAuditRequest(input: {
  type: AuditType;
  url: string;
  objectives?: string;
}): Promise<ApiMutationResult<{ id: number; number: string }>> {
  const res = await httpClient.post<ApiResponse<{ id: number; number: string }>>('/api/audits', input);
  if (!isApiOk(res.data)) throw new Error('Réponse API invalide.');
  return { data: res.data.data, message: res.data.message };
}

export async function fetchAuditMetadata(): Promise<AuditMetadataDto> {
  const res = await httpClient.get('/api/audits/metadata');
  return res.data.data;
}

export async function fetchMyAudits(page = 1, perPage = 10): Promise<PaginatedResult<AuditListItemDto>> {
  const res = await httpClient.get<ApiResponse<{ items: AuditListItemDto[]; meta: PaginationMeta }>>('/api/audits', {
    params: { page, perPage },
  });
  if (!isApiOk(res.data)) throw new Error(res.data.message || 'Impossible de charger vos audits.');
  return res.data.data;
}

export async function fetchMyAudit(
  id: number,
): Promise<AuditDetailDto & { events: AuditEventDto[] }> {
  const res = await httpClient.get(`/api/audits/${id}`);
  return res.data.data;
}

// Admin
export async function adminFetchAudits(
  page = 1,
  perPage = 10,
  filters: {
    from?: string | null;
    q?: string;
    sort?: string;
    status?: string;
    to?: string | null;
    type?: string;
  } = {},
): Promise<PaginatedResult<AuditListItemDto>> {
  const res = await httpClient.get<ApiResponse<{ items: AuditListItemDto[]; meta: PaginationMeta }>>('/api/admin/audits', {
    params: {
      page,
      perPage,
      from: filters.from || undefined,
      q: filters.q?.trim() || undefined,
      sort: filters.sort || undefined,
      status: filters.status && filters.status !== 'all' ? filters.status : undefined,
      to: filters.to || undefined,
      type: filters.type && filters.type !== 'all' ? filters.type : undefined,
    },
  });
  if (!isApiOk(res.data)) throw new Error(res.data.message || 'Impossible de charger les audits.');
  return res.data.data;
}

export async function adminFetchAudit(
  id: number,
): Promise<
  AuditDetailDto & { client: { id: number; name: string; email: string }; events: AuditEventDto[] }
> {
  const res = await httpClient.get(`/api/admin/audits/${id}`);
  return res.data.data;
}

export async function adminUpdateAuditStatus(
  id: number,
  status: AuditListItemDto['status'],
): Promise<void> {
  await httpClient.put(`/api/admin/audits/${id}/status`, { status });
}

export async function adminUpdateAuditItem(
  auditId: number,
  itemId: number,
  patch: Partial<Pick<AuditItemDto, 'isCompliant' | 'comment'>>,
): Promise<void> {
  await httpClient.put(`/api/admin/audits/${auditId}/items/${itemId}`, patch);
}

// PDF downloads
export async function adminDownloadAuditPdf(id: number): Promise<Blob> {
  const res = await httpClient.post(`/api/admin/audits/${id}/pdf`, null, { responseType: 'blob' });
  return res.data as Blob;
}
export async function adminDownloadAuditSummaryPdf(id: number): Promise<Blob> {
  const res = await httpClient.post(`/api/admin/audits/${id}/pdf-summary`, null, {
    responseType: 'blob',
  });
  return res.data as Blob;
}
export async function clientDownloadAuditPdf(id: number): Promise<Blob> {
  const res = await httpClient.post(`/api/audits/${id}/pdf`, null, { responseType: 'blob' });
  return res.data as Blob;
}
export async function clientDownloadAuditSummaryPdf(id: number): Promise<Blob> {
  const res = await httpClient.post(`/api/audits/${id}/pdf-summary`, null, {
    responseType: 'blob',
  });
  return res.data as Blob;
}
