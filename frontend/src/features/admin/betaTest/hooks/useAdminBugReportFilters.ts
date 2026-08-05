import { useState } from 'react';
import { useSearchParams } from 'react-router';

import type { AdminBugReportDto } from '../api';

export const useAdminBugReportFilters = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(1);
  const [commentPage, setCommentPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');
  const [search, setSearch] = useState('');
  const [assignedFilter, setAssignedFilter] = useState('');
  const [selectedReportId, setSelectedReportId] = useState<number | null>(
    Number(searchParams.get('reportId') ?? 0) || null,
  );

  const filters = {
    page,
    perPage: 12,
    status: statusFilter || undefined,
    severity: severityFilter || undefined,
    search: search.trim() || undefined,
    assignedTo: assignedFilter || undefined,
  };

  const openModal = (report: AdminBugReportDto) => {
    setSelectedReportId(report.id);
    setCommentPage(1);
    setSearchParams({ reportId: String(report.id) });
  };

  const closeModal = () => {
    setSelectedReportId(null);
    setCommentPage(1);
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
    setCommentPage,
    setPage,
    setSearch,
    setSeverityFilter,
    setStatusFilter,
  };
};
