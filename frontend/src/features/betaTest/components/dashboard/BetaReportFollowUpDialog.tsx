import {
  type BugReport,
  type BugReportComment,
  type PaginationMeta,
} from '../../api/betaApi';
import { useId } from 'react';
import { Dialog, DialogBackdrop, DialogPanel } from '@/shared/components/ui/dialog';
import { BetaReportConversation } from './reportFollowUp/BetaReportConversation';
import { BetaReportFollowUpHeader } from './reportFollowUp/BetaReportFollowUpHeader';
import { BetaReportSummary } from './reportFollowUp/BetaReportSummary';

interface BetaReportFollowUpDialogProps {
  commentPage: number;
  comments: BugReportComment[];
  commentsMeta: PaginationMeta | null;
  loadingComments: boolean;
  newCommentText: string;
  open: boolean;
  report: BugReport;
  sending: boolean;
  onClose: () => void;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onCommentTextChange: (value: string) => void;
  onSubmit: (event: React.FormEvent) => void;
}

export const BetaReportFollowUpDialog = ({
  commentPage,
  comments,
  commentsMeta,
  loadingComments,
  newCommentText,
  open,
  report,
  sending,
  onClose,
  onCommentPageChange,
  onCommentTextChange,
  onSubmit,
}: BetaReportFollowUpDialogProps) => {
  const titleId = useId();
  const descriptionId = useId();

  return (
    <Dialog open={open} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
        <DialogPanel
          className="flex max-h-[88vh] w-full max-w-3xl flex-col rounded-xl border border-brand-100 bg-white shadow-2xl"
          aria-labelledby={titleId}
          aria-describedby={descriptionId}
        >
          <BetaReportFollowUpHeader
            report={report}
            onClose={onClose}
            titleId={titleId}
            descriptionId={descriptionId}
          />
          <BetaReportSummary report={report} />
          <BetaReportConversation
            commentPage={commentPage}
            comments={comments}
            commentsMeta={commentsMeta}
            loadingComments={loadingComments}
            newCommentText={newCommentText}
            sending={sending}
            onCommentPageChange={onCommentPageChange}
            onCommentTextChange={onCommentTextChange}
            onSubmit={onSubmit}
          />
        </DialogPanel>
      </div>
    </Dialog>
  );
};
