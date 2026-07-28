interface NewsPaginationProps {
  page: number;
  totalPages: number;
  onPageChange: (page: number) => void;
}

export const NewsPagination = ({ page, totalPages, onPageChange }: NewsPaginationProps) => {
  if (totalPages <= 1) return null;

  return (
    <nav className="flex flex-wrap items-center justify-center gap-3" aria-label="Pagination des actualités">
      <button
        type="button"
        className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-800 disabled:opacity-50"
        disabled={page <= 1}
        onClick={() => onPageChange(page - 1)}
      >
        Page précédente
      </button>
      <span className="text-sm text-stone-600">
        Page {page} sur {totalPages}
      </span>
      <button
        type="button"
        className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-800 disabled:opacity-50"
        disabled={page >= totalPages}
        onClick={() => onPageChange(page + 1)}
      >
        Page suivante
      </button>
    </nav>
  );
};
