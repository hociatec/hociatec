import { useCallback, useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { fetchAdminOrders, fetchAdminOrderMetadata, updateAdminOrderStatus, type OrderDto } from '@/features/orders/publicApi';
import {
  type OrderHealthFilter,
  type OrderSortKey,
  type OrderStatus,
  type OrderStatusFilter,
} from '../lib/adminOrderList';
import { adminOrderQueryKeys } from '@/features/admin/orders/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

export const useAdminOrdersList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const [filter, setFilter] = useState<OrderStatusFilter>(
    (searchParams.get('status') as OrderStatusFilter | null) ?? 'all',
  );
  const [health, setHealth] = useState<OrderHealthFilter>(
    (searchParams.get('health') as OrderHealthFilter | null) ?? 'all',
  );
  const [search, setSearch] = useState(searchParams.get('search') ?? '');
  const [sort, setSort] = useState<OrderSortKey>(
    (searchParams.get('sort') as OrderSortKey | null) ?? 'newest',
  );
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<{
    id: number;
    current: OrderStatus;
    next: OrderStatus;
    options: OrderStatus[];
    order: OrderDto;
  } | null>(null);
  const [updateError, setUpdateError] = useState<string | null>(null);
  const metadataQuery = useQuery({
    queryKey: adminOrderQueryKeys.metadata(),
    queryFn: fetchAdminOrderMetadata,
  });
  const ordersQuery = useQuery<PaginatedResult<OrderDto>, Error>({
    queryKey: [...adminOrderQueryKeys.list(filter, health, search, sort), { page }],
    queryFn: () => fetchAdminOrders(filter, health, search, sort, page, 10),
  });
  const updateStatusMutation = useMutation({
    mutationFn: ({ id, next }: { id: number; next: OrderStatus }) => updateAdminOrderStatus(id, next),
    onSuccess: (updated) => {
      queryClient.setQueryData<PaginatedResult<OrderDto>>(
        [...adminOrderQueryKeys.list(filter, health, search, sort), { page }],
        (previous) =>
          previous
            ? { ...previous, items: previous.items.map((order) => (order.id === updated.id ? updated : order)) }
            : previous,
      );
      void queryClient.invalidateQueries({ queryKey: adminOrderQueryKeys.detail(updated.id) });
      setEditing(null);
      setUpdateError(null);
    },
    onError: (e) =>
      setUpdateError(getHttpErrorMessage(e, 'Impossible de mettre à jour le statut.')),
  });
  const orders = ordersQuery.data?.items ?? [];
  const pagination = ordersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const status = ordersQuery.isLoading ? 'loading' : ordersQuery.isError ? 'error' : 'success';
  const error = ordersQuery.error ? getHttpErrorMessage(ordersQuery.error, 'Erreur') : null;
  const statusOptions =
    (metadataQuery.data?.statuses as { value: OrderStatus; label: string }[] | undefined) ?? [];
  useEffect(() => {
    const next = new URLSearchParams();
    if (filter !== 'all') next.set('status', filter);
    if (health !== 'all') next.set('health', health);
    if (search.trim()) next.set('search', search.trim());
    if (sort !== 'newest') next.set('sort', sort);
    setSearchParams(next, { replace: true });
  }, [filter, health, search, sort, setSearchParams]);
  useEffect(() => {
    setPage(1);
  }, [filter, health, search, sort]);
  const handleConfirmUpdate = useCallback(() => {
    if (!editing) return;
    if (!editing.options.length) {
      setUpdateError('Aucune transition disponible pour ce statut.');
      return;
    }
    updateStatusMutation.mutate({ id: editing.id, next: editing.next });
  }, [editing, updateStatusMutation]);
  const handleEditStatus = useCallback(
    (order: OrderDto, options: OrderStatus[]) => {
      if (!options.length) return;
      const [next] = options;
      setEditing({
        id: order.id,
        current: (order.status as OrderStatus) ?? 'pending',
        next: next ?? 'pending',
        options,
        order,
      });
    },
    [setEditing],
  );
  return {
    orders,
    pagination,
    setPage,
    status,
    error,
    filter,
    setFilter,
    statusOptions,
    health,
    setHealth,
    search,
    setSearch,
    sort,
    setSort,
    editing,
    setEditing,
    updateError,
    setUpdateError,
    handleEditStatus,
    handleConfirmUpdate,
  };
};
