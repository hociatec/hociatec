import { useState } from 'react';

import type { AdminCampaignDto } from '../api';
import { emptyCampaignForm, type CampaignFormState } from '../lib/campaignForms';
import { formatApiDateForDateInput } from '@/shared/lib/formatters';

export const ADMIN_CAMPAIGN_REPORTS_PER_PAGE = 6;

export const useAdminCampaignDialogState = () => {
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [selectedCampaign, setSelectedCampaign] = useState<AdminCampaignDto | null>(null);
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [reportsPage, setReportsPage] = useState(1);
  const [commentsPage, setCommentsPage] = useState(1);
  const [addForm, setAddForm] = useState(emptyCampaignForm);
  const [editForm, setEditForm] = useState<CampaignFormState>({
    name: '',
    description: '',
    status: 'draft',
    startsAt: '',
    endsAt: '',
  });

  const openEdit = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setEditForm({
      name: campaign.name,
      description: campaign.description,
      status: campaign.status,
      startsAt: formatApiDateForDateInput(campaign.startsAt),
      endsAt: formatApiDateForDateInput(campaign.endsAt),
    });
    setIsEditOpen(true);
  };

  const openDetail = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setReportsPage(1);
    setCommentsPage(1);
    setSelectedReportId(null);
    setIsDetailOpen(true);
  };

  const closeReportMessages = () => {
    setSelectedReportId(null);
    setCommentsPage(1);
  };

  const selectedCampaignReports = selectedCampaign?.reports ?? [];
  const reportsPageCount = Math.max(
    1,
    Math.ceil(selectedCampaignReports.length / ADMIN_CAMPAIGN_REPORTS_PER_PAGE),
  );
  const visibleCampaignReports = selectedCampaignReports.slice(
    (reportsPage - 1) * ADMIN_CAMPAIGN_REPORTS_PER_PAGE,
    reportsPage * ADMIN_CAMPAIGN_REPORTS_PER_PAGE,
  );
  const selectedReport = selectedCampaignReports.find((report) => report.id === selectedReportId);

  return {
    addForm,
    closeReportMessages,
    commentsPage,
    editForm,
    isAddOpen,
    isDetailOpen,
    isEditOpen,
    newCommentText,
    openDetail,
    openEdit,
    reportsPage,
    reportsPageCount,
    selectedCampaign,
    selectedReport,
    selectedReportId,
    setAddForm,
    setCommentsPage,
    setEditForm,
    setIsAddOpen,
    setIsDetailOpen,
    setIsEditOpen,
    setNewCommentText,
    setReportsPage,
    setSelectedCampaign,
    setSelectedReportId,
    visibleCampaignReports,
  };
};
