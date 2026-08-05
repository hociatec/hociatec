interface PaginationControlsProps {
  className?: string | undefined;
  page: number;
  total?: number | undefined;
  totalLabel?: string | undefined;
  totalPages: number;
  onPageChange: (updater: (page: number) => number) => void;
}

export const PaginationControls = ({
  className = 'mt-6',
  page,
  total,
  totalLabel,
  totalPages,
  onPageChange,
}: PaginationControlsProps) => {
  if (totalPages <= 1) return null;

  return (
    <div className={`${className} flex items-center justify-center gap-3`}>
      <button
        type="button"
        disabled={page <= 1}
        onClick={() => onPageChange((value) => Math.max(1, value - 1))}
        className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
      >
        Page précédente
      </button>
      <span className="text-sm text-stone-600">
        Page {page} sur {totalPages}
        {typeof total === 'number' && totalLabel ? ` · ${total} ${totalLabel}${total > 1 ? 's' : ''}` : ''}
      </span>
      <button
        type="button"
        disabled={page >= totalPages}
        onClick={() => onPageChange((value) => Math.min(totalPages, value + 1))}
        className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
      >
        Page suivante
      </button>
    </div>
  );
};
