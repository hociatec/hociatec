import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { fetchAdminOrders, fetchAdminOrderMetadata, updateAdminOrderStatus, type OrderDto } from '@/features/orders/publicApi';
import {
  filterAndSortAdminOrders,
  type OrderHealthFilter,
  type OrderSortKey,
  type OrderStatus,
  type OrderStatusFilter,
} from '../lib/adminOrderList';
import { adminOrderQueryKeys } from '@/shared/lib/queryKeys';

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
  const ordersQuery = useQuery<OrderDto[], Error>({
    queryKey: adminOrderQueryKeys.list(filter, health),
    queryFn: () => fetchAdminOrders(filter, health),
  });
  const updateStatusMutation = useMutation({
    mutationFn: ({ id, next }: { id: number; next: OrderStatus }) => updateAdminOrderStatus(id, next),
    onSuccess: (updated) => {
      queryClient.setQueryData<OrderDto[]>(
        adminOrderQueryKeys.list(filter, health),
        (previous = []) => previous.map((order) => (order.id === updated.id ? updated : order)),
      );
      void queryClient.invalidateQueries({ queryKey: adminOrderQueryKeys.detail(updated.id) });
      setEditing(null);
      setUpdateError(null);
    },
    onError: (e) =>
      setUpdateError(getHttpErrorMessage(e, 'Impossible de mettre à jour le statut.')),
  });
  const orders = ordersQuery.data ?? [];
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
  const filteredOrders = useMemo(
    () => filterAndSortAdminOrders(orders, search, sort),
    [orders, search, sort],
  );
  const handleConfirmUpdate = () => {
    if (!editing) return;
    if (!editing.options.length) {
      setUpdateError('Aucune transition disponible pour ce statut.');
      return;
    }
    updateStatusMutation.mutate({ id: editing.id, next: editing.next });
  };
  return {
    orders,
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
    filteredOrders,
    handleConfirmUpdate,
  };
};
