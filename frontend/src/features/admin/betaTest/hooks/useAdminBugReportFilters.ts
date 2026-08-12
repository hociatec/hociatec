import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';

import type { AdminBugReportDto } from '../api';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useAdminBugReportFilters = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const initialReportId = parseNullablePositiveInteger(searchParams.get('reportId'));
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const [commentPage, setCommentPage] = useState(parseNullablePositiveInteger(searchParams.get('commentPage')) ?? 1);
  const [activityPage, setActivityPage] = useState(parseNullablePositiveInteger(searchParams.get('activityPage')) ?? 1);
  const [statusFilter, setStatusFilter] = useState(searchParams.get('status') ?? '');
  const [severityFilter, setSeverityFilter] = useState(searchParams.get('severity') ?? '');
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [assignedFilter, setAssignedFilter] = useState(searchParams.get('assignedTo') ?? '');
  const [selectedReportId, setSelectedReportId] = useState<number | null>(
    initialReportId,
  );

  const filters = omitUndefinedProperties({
    page,
    perPage: 10,
    status: statusFilter || undefined,
    severity: severityFilter || undefined,
    search: search.trim() || undefined,
    assignedTo: assignedFilter || undefined,
  });

  const openModal = (report: AdminBugReportDto) => {
    setSelectedReportId(report.id);
    setCommentPage(1);
    setActivityPage(1);
    const next = new URLSearchParams(searchParams);
    next.set('reportId', String(report.id));
    next.delete('commentPage');
    next.delete('activityPage');
    setSearchParams(next, { replace: true });
  };

  const closeModal = () => {
    setSelectedReportId(null);
    setCommentPage(1);
    setActivityPage(1);
    const next = new URLSearchParams(searchParams);
    next.delete('reportId');
    next.delete('commentPage');
    next.delete('activityPage');
    setSearchParams(next, { replace: true });
  };

  const resetFilters = () => {
    setPage(1);
    setStatusFilter('');
    setSeverityFilter('');
    setAssignedFilter('');
    setSearch('');
  };

  useEffect(() => {
    const next = new URLSearchParams(searchParams);
    if (page > 1) next.set('page', String(page));
    else next.delete('page');
    if (commentPage > 1 && selectedReportId) next.set('commentPage', String(commentPage));
    else next.delete('commentPage');
    if (activityPage > 1 && selectedReportId) next.set('activityPage', String(activityPage));
    else next.delete('activityPage');
    if (statusFilter) next.set('status', statusFilter);
    else next.delete('status');
    if (severityFilter) next.set('severity', severityFilter);
    else next.delete('severity');
    if (search.trim()) next.set('q', search.trim());
    else next.delete('q');
    if (assignedFilter) next.set('assignedTo', assignedFilter);
    else next.delete('assignedTo');
    if (selectedReportId) next.set('reportId', String(selectedReportId));
    else next.delete('reportId');
    setSearchParams(next, { replace: true });
  }, [
    activityPage,
    assignedFilter,
    commentPage,
    page,
    search,
    searchParams,
    selectedReportId,
    setSearchParams,
    severityFilter,
    statusFilter,
  ]);

  return {
    assignedFilter,
    activityPage,
    commentPage,
    filters,
    page,
    search,
    selectedReportId,
    severityFilter,
    statusFilter,
    closeModal,
    openModal,
    resetFilters,
    setAssignedFilter,
    setActivityPage,
    setCommentPage,
    setPage,
    setSearch,
    setSeverityFilter,
    setStatusFilter,
  };
};
