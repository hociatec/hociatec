import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { deletePromotion, fetchPromotionAudiences, fetchPromotions } from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminPromotionQueryKeys } from '@/features/admin/promotions/queryKeys';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const usePromotionsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [query, setQuery] = useState(searchParams.get('q') ?? '');
  const [statusFilter, setStatusFilter] = useState(searchParams.get('status') ?? 'all');
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const debouncedQuery = useDebounce(query.trim(), 250);
  const overviewQuery = useQuery({
    queryKey: [...adminPromotionQueryKeys.overview(), { page, q: debouncedQuery, status: statusFilter }],
    queryFn: async () => {
      const [promotions, audiences] = await Promise.all([
        fetchPromotions(page, 10, debouncedQuery || undefined, statusFilter === 'all' ? undefined : statusFilter),
        fetchPromotionAudiences(),
      ]);
      return { promotions, audiences };
    },
  });
  const promotions = overviewQuery.data?.promotions.items ?? [];
  const pagination = overviewQuery.data?.promotions.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const audiences = overviewQuery.data?.audiences ?? {};
  const deleteMutation = useMutation({
    mutationFn: deletePromotion,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminPromotionQueryKeys.overview() });
      toast.show(response.message ?? 'La promotion a bien été supprimée.', { variant: 'success' });
    },
    onError: (e) => {
      const message = getHttpErrorMessage(e, 'Suppression impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    },
  });
  const filteredPromotions = useMemo(() => promotions, [promotions]);
  useEffect(() => {
    setPage(1);
  }, [debouncedQuery, statusFilter]);
  useEffect(() => {
    const next = new URLSearchParams();
    if (query.trim()) {
      next.set('q', query.trim());
    }
    if (statusFilter !== 'all') {
      next.set('status', statusFilter);
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, query, setSearchParams, statusFilter]);
  const handleDelete = async (promotionId: number) => {
    const promotion = promotions.find((item) => item.id === promotionId);
    if (
      !(await confirm({
        title: 'Supprimer la promotion',
        description: `Supprimer ${promotion ? `"${promotion.name}" (${promotion.slug})` : 'cette promotion'} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    deleteMutation.mutate(promotionId);
  };
  return {
    promotions,
    audiences,
    query,
    setQuery,
    statusFilter,
    setStatusFilter,
    loading: overviewQuery.isLoading,
    error:
      error ??
      (overviewQuery.error
        ? getHttpErrorMessage(overviewQuery.error, 'Impossible de charger les promotions.')
        : null),
    filteredPromotions,
    pagination,
    setPage,
    handleDelete,
  };
};
