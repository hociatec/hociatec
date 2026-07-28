import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { fetchAdminOrders, fetchAdminOrderMetadata, updateAdminOrderStatus, type OrderDto } from '@/features/orders/api';
import {
  filterAndSortAdminOrders,
  type OrderHealthFilter,
  type OrderSortKey,
  type OrderStatus,
  type OrderStatusFilter,
} from '../lib/adminOrderList';

export const useAdminOrdersList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
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
  const [statusOptions, setStatusOptions] = useState<{ value: OrderStatus; label: string }[]>([]);
  useEffect(() => {
    void fetchAdminOrderMetadata().then((metadata) => setStatusOptions(metadata.statuses as { value: OrderStatus; label: string }[])).catch(() => undefined);
  }, []);
  useEffect(() => {
    setStatus('loading');
    setError(null);
    void fetchAdminOrders(filter, health)
      .then((items) => {
        setOrders(items);
        setStatus('success');
      })
      .catch((e) => {
        setError(getHttpErrorMessage(e, 'Erreur'));
        setStatus('error');
      });
  }, [filter, health]);
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
    void updateAdminOrderStatus(editing.id, editing.next)
      .then((updated) => {
        setOrders((previous) =>
          previous.map((order) => (order.id === updated.id ? updated : order)),
        );
        setEditing(null);
        setUpdateError(null);
      })
      .catch((e) =>
        setUpdateError(getHttpErrorMessage(e, 'Impossible de mettre à jour le statut.')),
      );
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
