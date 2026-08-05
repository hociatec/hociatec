import { useEffect } from 'react';

import {
  ADMIN_CAMPAIGN_REPORTS_PER_PAGE,
  useAdminCampaignDialogState,
} from './useAdminCampaignDialogState';
import { useAdminCampaignMutations } from './useAdminCampaignMutations';
import { useAdminCampaignQueries } from './useAdminCampaignQueries';

export const useAdminBetaCampaignsController = () => {
  const dialogs = useAdminCampaignDialogState();
  const queries = useAdminCampaignQueries({
    commentsPage: dialogs.commentsPage,
    selectedReportId: dialogs.selectedReportId,
  });
  const mutations = useAdminCampaignMutations({
    addForm: dialogs.addForm,
    editForm: dialogs.editForm,
    newCommentText: dialogs.newCommentText,
    selectedCampaign: dialogs.selectedCampaign,
    selectedReportId: dialogs.selectedReportId,
    onAddClose: (form) => {
      dialogs.setIsAddOpen(false);
      dialogs.setAddForm(form);
    },
    onEditClose: () => {
      dialogs.setIsEditOpen(false);
      dialogs.setSelectedCampaign(null);
    },
    onCommentReset: () => dialogs.setNewCommentText(''),
  });

  useEffect(() => {
    if (!dialogs.isDetailOpen) return;

    const preventEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
      }
    };

    document.addEventListener('keydown', preventEscape, true);

    return () => document.removeEventListener('keydown', preventEscape, true);
  }, [dialogs.isDetailOpen]);

  return {
    ...dialogs,
    ...queries,
    ...mutations,
  };
};

export { ADMIN_CAMPAIGN_REPORTS_PER_PAGE };
