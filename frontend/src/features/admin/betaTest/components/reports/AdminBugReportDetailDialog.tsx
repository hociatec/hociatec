import type {
  AdminBugReportActivityDto,
  AdminBugReportDto,
  BugReportCommentDto,
  PaginationMeta,
} from '../../api';
import { Dialog, DialogBackdrop, DialogPanel } from '@/shared/components/ui/dialog';
import {
  AdminBugReportActivityLog,
  AdminBugReportDialogHeader,
  AdminBugReportMainDetails,
} from './AdminBugReportDetailContent';
import { AdminBugReportDiscussion } from './AdminBugReportDiscussion';

interface AdminBugReportDetailDialogProps {
  activities: AdminBugReportActivityDto[];
  activitiesMeta: PaginationMeta | null;
  activityPage: number;
  commentPage: number;
  comments: BugReportCommentDto[];
  commentsMeta: PaginationMeta | null;
  duplicateOfId: string;
  duplicatePending: boolean;
  duplicateReason: string;
  loadingComments: boolean;
  newCommentText: string;
  postCommentPending: boolean;
  report: AdminBugReportDto | undefined;
  onClose: () => void;
  onActivityPageChange: (updater: (page: number) => number) => void;
  onCommentPageChange: (updater: (page: number) => number) => void;
  onDuplicateIdChange: (value: string) => void;
  onDuplicateReasonChange: (value: string) => void;
  onDuplicateSubmit: (payload: { id: number; duplicateOfId: number; reason?: string }) => void;
  onNewCommentTextChange: (value: string) => void;
  onPostComment: () => void;
}

export const AdminBugReportDetailDialog = ({
  activities,
  activitiesMeta,
  activityPage,
  commentPage,
  comments,
  commentsMeta,
  duplicateOfId,
  duplicatePending,
  duplicateReason,
  loadingComments,
  newCommentText,
  postCommentPending,
  report,
  onClose,
  onActivityPageChange,
  onCommentPageChange,
  onDuplicateIdChange,
  onDuplicateReasonChange,
  onDuplicateSubmit,
  onNewCommentTextChange,
  onPostComment,
}: AdminBugReportDetailDialogProps) => {
  if (!report) return null;

  return (
    <Dialog open={Boolean(report)} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
          <AdminBugReportDialogHeader report={report} onClose={onClose} />

          <div className="grid flex-1 overflow-y-auto md:grid-cols-[1.1fr_0.9fr]">
            <AdminBugReportMainDetails
              duplicateOfId={duplicateOfId}
              duplicatePending={duplicatePending}
              duplicateReason={duplicateReason}
              report={report}
              onDuplicateIdChange={onDuplicateIdChange}
              onDuplicateReasonChange={onDuplicateReasonChange}
              onDuplicateSubmit={onDuplicateSubmit}
            />

            <section className="flex min-h-[520px] flex-col">
              <AdminBugReportDiscussion
                commentPage={commentPage}
                comments={comments}
                commentsMeta={commentsMeta}
                loadingComments={loadingComments}
                newCommentText={newCommentText}
                postCommentPending={postCommentPending}
                onCommentPageChange={onCommentPageChange}
                onNewCommentTextChange={onNewCommentTextChange}
                onPostComment={onPostComment}
              />
              <AdminBugReportActivityLog
                activities={activities}
                activityPage={activityPage}
                activitiesMeta={activitiesMeta}
                onActivityPageChange={onActivityPageChange}
              />
            </section>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
