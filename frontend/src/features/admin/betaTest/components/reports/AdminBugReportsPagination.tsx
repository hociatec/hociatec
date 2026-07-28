import type { PaginationMeta } from '../../api';

interface AdminBugReportsPaginationProps {
  meta: PaginationMeta | null;
  page: number;
  onPageChange: (updater: (page: number) => number) => void;
}

export const AdminBugReportsPagination = ({ meta, page, onPageChange }: AdminBugReportsPaginationProps) => {
  if (!meta || meta.totalPages <= 1) return null;

  return (
    <div className="mt-6 flex items-center justify-center gap-3">
      <button type="button" disabled={page <= 1} onClick={() => onPageChange((value) => Math.max(1, value - 1))} className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50">Page précédente</button>
      <span className="text-sm text-stone-600">Page {meta.page} sur {meta.totalPages} · {meta.total} signalement{meta.total > 1 ? 's' : ''}</span>
      <button type="button" disabled={page >= meta.totalPages} onClick={() => onPageChange((value) => Math.min(meta.totalPages, value + 1))} className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50">Page suivante</button>
    </div>
  );
};
