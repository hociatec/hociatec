import { useState } from 'react';
import { useSearchParams } from 'react-router';

import type { AdminBugReportDto } from '../api';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useAdminBugReportFilters = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const initialReportId = parseNullablePositiveInteger(searchParams.get('reportId'));
  const [page, setPage] = useState(1);
  const [commentPage, setCommentPage] = useState(1);
  const [activityPage, setActivityPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');
  const [search, setSearch] = useState('');
  const [assignedFilter, setAssignedFilter] = useState('');
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
    setSearchParams({ reportId: String(report.id) });
  };

  const closeModal = () => {
    setSelectedReportId(null);
    setCommentPage(1);
    setActivityPage(1);
    setSearchParams({});
  };

  const resetFilters = () => {
    setPage(1);
    setStatusFilter('');
    setSeverityFilter('');
    setAssignedFilter('');
    setSearch('');
  };

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
