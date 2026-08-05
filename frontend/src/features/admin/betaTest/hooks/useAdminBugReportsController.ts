import { useState } from 'react';

import { useAuth } from '@/features/auth/publicApi';
import { useAdminBugReportFilters } from './useAdminBugReportFilters';
import { useAdminBugReportMutations } from './useAdminBugReportMutations';
import { useAdminBugReportQueries } from './useAdminBugReportQueries';

export const useAdminBugReportsController = () => {
  const { user } = useAuth();
  const filters = useAdminBugReportFilters();
  const queries = useAdminBugReportQueries({
    commentPage: filters.commentPage,
    filters: filters.filters,
    selectedReportId: filters.selectedReportId,
  });
  const [newCommentText, setNewCommentText] = useState('');
  const [duplicateOfId, setDuplicateOfId] = useState('');
  const [duplicateReason, setDuplicateReason] = useState('');
  const mutations = useAdminBugReportMutations({
    newCommentText,
    selectedReportId: filters.selectedReportId,
    onCloseModal: filters.closeModal,
    onCommentReset: () => setNewCommentText(''),
    onDuplicateReset: () => {
      setDuplicateOfId('');
      setDuplicateReason('');
    },
  });

  const useMyReports = () => {
    if (!user?.id) return;
    filters.setAssignedFilter(String(user.id));
    filters.setPage(1);
  };

  return {
    ...queries,
    ...mutations,
    assignedFilter: filters.assignedFilter,
    commentPage: filters.commentPage,
    duplicateOfId,
    duplicateReason,
    filters: filters.filters,
    newCommentText,
    page: filters.page,
    search: filters.search,
    severityFilter: filters.severityFilter,
    statusFilter: filters.statusFilter,
    closeModal: filters.closeModal,
    openModal: filters.openModal,
    resetFilters: filters.resetFilters,
    setAssignedFilter: filters.setAssignedFilter,
    setCommentPage: filters.setCommentPage,
    setDuplicateOfId,
    setDuplicateReason,
    setNewCommentText,
    setPage: filters.setPage,
    setSearch: filters.setSearch,
    setSeverityFilter: filters.setSeverityFilter,
    setStatusFilter: filters.setStatusFilter,
    useMyReports,
  };
};
