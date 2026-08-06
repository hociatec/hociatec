import { useEffect, useMemo, useState } from 'react';

export const ADMIN_PAGE_SIZE = 10;

export const useAdminPagination = <T,>(items: T[], resetKey?: string) => {
  const [page, setPage] = useState(1);
  const totalPages = Math.max(1, Math.ceil(items.length / ADMIN_PAGE_SIZE));
  const currentPage = Math.min(page, totalPages);

  useEffect(() => {
    setPage(1);
  }, [resetKey]);

  useEffect(() => {
    if (page > totalPages) {
      setPage(totalPages);
    }
  }, [page, totalPages]);

  const paginatedItems = useMemo(
    () => items.slice((currentPage - 1) * ADMIN_PAGE_SIZE, currentPage * ADMIN_PAGE_SIZE),
    [items, currentPage],
  );

  return {
    page: currentPage,
    paginatedItems,
    setPage,
    total: items.length,
    totalPages,
  };
};
