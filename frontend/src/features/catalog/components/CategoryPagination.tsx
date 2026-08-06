import { useMemo } from 'react';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

type CategoryPaginationProps = {
  page: number;
  totalPages: number;
  updatePage: (page: number) => void;
};

export const CategoryPagination = ({ page, totalPages, updatePage }: CategoryPaginationProps) => {
  const pageNumbers = useMemo(() => {
    const start = clampAtLeast(page - 2, 1);
    const end = clampWithin(page + 2, 1, totalPages);
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  }, [page, totalPages]);

  if (totalPages <= 1) return null;

  return (
    <nav className="catalog-pagination" aria-label="Pagination des produits">
      <button
        type="button"
        className="catalog-pagination__button"
        disabled={page <= 1}
        onClick={() => updatePage(page - 1)}
      >
        Précédent
      </button>
      {pageNumbers.map((pageNumber) => (
        <button
          key={pageNumber}
          type="button"
          className={`catalog-pagination__button${pageNumber === page ? ' is-active' : ''}`}
          onClick={() => updatePage(pageNumber)}
          aria-current={pageNumber === page ? 'page' : undefined}
        >
          {pageNumber}
        </button>
      ))}
      <button
        type="button"
        className="catalog-pagination__button"
        disabled={page >= totalPages}
        onClick={() => updatePage(page + 1)}
      >
        Suivant
      </button>
    </nav>
  );
};
