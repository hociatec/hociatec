import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deletePromotion, fetchPromotionAudiences, fetchPromotions } from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { adminPromotionQueryKeys } from '@/shared/lib/queryKeys';

export const usePromotionsList = () => {
  const confirm = useConfirm();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const overviewQuery = useQuery({
    queryKey: [...adminPromotionQueryKeys.overview(), { page }],
    queryFn: async () => {
      const [promotions, audiences] = await Promise.all([
        fetchPromotions(page, 10),
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
  const filteredPromotions = useMemo(() => {
    const term = query.trim().toLowerCase();
    return promotions.filter(
      (promotion) =>
        (!term ||
          promotion.name.toLowerCase().includes(term) ||
          promotion.slug.toLowerCase().includes(term)) &&
        (statusFilter === 'all' ||
          (statusFilter === 'active' && promotion.isActive) ||
          (statusFilter === 'inactive' && !promotion.isActive)),
    );
  }, [promotions, query, statusFilter]);
  useEffect(() => {
    setPage(1);
  }, [query, statusFilter]);
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
