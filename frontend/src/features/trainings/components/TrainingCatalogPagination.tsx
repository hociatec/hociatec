type TrainingCatalogPaginationProps = {
  currentPage: number;
  totalPages: number;
  updatePage: (page: number) => void;
};

export const TrainingCatalogPagination = ({
  currentPage,
  totalPages,
  updatePage,
}: TrainingCatalogPaginationProps) => {
  if (totalPages <= 1) return null;

  const pageNumbers = Array.from({ length: totalPages }, (_, index) => index + 1);

  return (
    <nav
      className="flex flex-wrap items-center justify-center gap-2"
      aria-label="Pagination des formations"
    >
      <button
        type="button"
        className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 disabled:opacity-40"
        disabled={currentPage === 1}
        onClick={() => updatePage(currentPage - 1)}
      >
        Précédent
      </button>
      {pageNumbers.map((pageNumber) => (
        <button
          key={pageNumber}
          type="button"
          className={`rounded-full border px-4 py-2 text-sm font-semibold ${pageNumber === currentPage ? 'border-brand-900 bg-brand-900 text-white' : 'border-brand-200 text-stone-700'}`}
          aria-current={pageNumber === currentPage ? 'page' : undefined}
          onClick={() => updatePage(pageNumber)}
        >
          {pageNumber}
        </button>
      ))}
      <button
        type="button"
        className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 disabled:opacity-40"
        disabled={currentPage === totalPages}
        onClick={() => updatePage(currentPage + 1)}
      >
        Suivant
      </button>
    </nav>
  );
};
