import { useEffect, useMemo, useState } from 'react';

import { clampAtLeast, clampWithin } from '@/shared/lib/number';

export const ADMIN_PAGE_SIZE = 10;

export const useAdminPagination = <T,>(items: T[], resetKey?: string) => {
  const [page, setPage] = useState(1);
  const totalPages = clampAtLeast(Math.ceil(items.length / ADMIN_PAGE_SIZE), 1);
  const currentPage = clampWithin(page, 1, totalPages);

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
