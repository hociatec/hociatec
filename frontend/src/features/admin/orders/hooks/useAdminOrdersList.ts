import { useCallback, useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { fetchAdminOrders, fetchAdminOrderMetadata, updateAdminOrderStatus, type OrderDto } from '@/features/orders/publicApi';
import { bulkUpdateOrderStatus } from '@/features/admin/operations/api';
import {
  type OrderHealthFilter,
  type OrderSortKey,
  type OrderStatus,
  type OrderStatusFilter,
} from '../lib/adminOrderList';
import { adminOrderQueryKeys } from '@/features/admin/orders/queryKeys';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
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
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [sort, setSort] = useState<OrderSortKey>(
    (searchParams.get('sort') as OrderSortKey | null) ?? 'newest',
  );
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const [editing, setEditing] = useState<{
    id: number;
    current: OrderStatus;
    next: OrderStatus;
    options: OrderStatus[];
    order: OrderDto;
  } | null>(null);
  const [updateError, setUpdateError] = useState<string | null>(null);
  const [selectedOrderIds, setSelectedOrderIds] = useState<number[]>([]);
  const [bulkStatus, setBulkStatus] = useState<OrderStatus>('confirmed');
  const [bulkMessage, setBulkMessage] = useState<string | null>(null);
  const [bulkError, setBulkError] = useState<string | null>(null);
  const debouncedSearch = useDebounce(search.trim(), 250);
  const metadataQuery = useQuery({
    queryKey: adminOrderQueryKeys.metadata(),
    queryFn: fetchAdminOrderMetadata,
  });
  const ordersQuery = useQuery<PaginatedResult<OrderDto>, Error>({
    queryKey: [...adminOrderQueryKeys.list(filter, health, debouncedSearch, sort), { page }],
    queryFn: () => fetchAdminOrders(filter, health, debouncedSearch, sort, page, 10),
  });
  const updateStatusMutation = useMutation({
    mutationFn: ({ id, next }: { id: number; next: OrderStatus }) => updateAdminOrderStatus(id, next),
    onSuccess: (updated) => {
      queryClient.setQueryData<PaginatedResult<OrderDto>>(
        [...adminOrderQueryKeys.list(filter, health, debouncedSearch, sort), { page }],
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
  const bulkUpdateMutation = useMutation({
    mutationFn: ({ orderIds, status }: { orderIds: number[]; status: OrderStatus }) =>
      bulkUpdateOrderStatus(orderIds, status),
    onSuccess: async (updatedCount, variables) => {
      await queryClient.invalidateQueries({ queryKey: ['admin', 'orders'] });
      setSelectedOrderIds([]);
      setBulkError(null);
      setBulkMessage(
        updatedCount === variables.orderIds.length
          ? `${updatedCount} commande(s) mise(s) à jour.`
          : `${updatedCount} commande(s) mise(s) à jour sur ${variables.orderIds.length} sélectionnée(s).`,
      );
    },
    onError: (error) => {
      setBulkMessage(null);
      setBulkError(getHttpErrorMessage(error, 'Impossible de mettre à jour les commandes sélectionnées.'));
    },
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
    if (search.trim()) next.set('q', search.trim());
    if (sort !== 'newest') next.set('sort', sort);
    if (page > 1) next.set('page', String(page));
    setSearchParams(next, { replace: true });
  }, [filter, health, page, search, sort, setSearchParams]);
  useEffect(() => {
    setPage(1);
  }, [filter, health, debouncedSearch, sort]);
  useEffect(() => {
    setSelectedOrderIds((current) => current.filter((id) => orders.some((order) => order.id === id)));
  }, [orders]);
  useEffect(() => {
    if (statusOptions.length > 0 && !statusOptions.some((option) => option.value === bulkStatus)) {
      setBulkStatus(statusOptions[0]?.value ?? 'confirmed');
    }
  }, [bulkStatus, statusOptions]);
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
  const toggleSelectedOrder = useCallback((orderId: number) => {
    setBulkMessage(null);
    setBulkError(null);
    setSelectedOrderIds((current) =>
      current.includes(orderId) ? current.filter((id) => id !== orderId) : [...current, orderId],
    );
  }, []);
  const toggleVisibleOrders = useCallback(() => {
    const visibleIds = orders.map((order) => order.id);
    const allVisibleSelected = visibleIds.length > 0 && visibleIds.every((id) => selectedOrderIds.includes(id));

    setBulkMessage(null);
    setBulkError(null);
    setSelectedOrderIds(allVisibleSelected ? [] : visibleIds);
  }, [orders, selectedOrderIds]);
  const submitBulkUpdate = useCallback(() => {
    if (selectedOrderIds.length === 0) {
      setBulkMessage(null);
      setBulkError('Sélectionne au moins une commande.');
      return;
    }

    bulkUpdateMutation.mutate({ orderIds: selectedOrderIds, status: bulkStatus });
  }, [bulkStatus, bulkUpdateMutation, selectedOrderIds]);

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
    selectedOrderIds,
    bulkStatus,
    setBulkStatus,
    bulkMessage,
    bulkError,
    bulkUpdating: bulkUpdateMutation.isPending,
    toggleSelectedOrder,
    toggleVisibleOrders,
    submitBulkUpdate,
  };
};
