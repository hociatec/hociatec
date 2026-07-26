import { useEffect, useMemo, useState } from 'react';
import { deletePromotion, fetchPromotionAudiences, fetchPromotions, type Promotion } from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';

export const usePromotionsList = () => {
  const confirm = useConfirm();
  const toast = useToast();
  const [promotions, setPromotions] = useState<Promotion[]>([]);
  const [audiences, setAudiences] = useState<
    Record<string, { label: string; description: string }>
  >({});
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    setLoading(true);
    void Promise.all([fetchPromotions(), fetchPromotionAudiences()])
      .then(([items, audienceItems]) => {
        setPromotions(items);
        setAudiences(audienceItems);
      })
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger les promotions.')))
      .finally(() => setLoading(false));
  }, []);
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
    try {
      await deletePromotion(promotionId);
      setPromotions((items) => items.filter((item) => item.id !== promotionId));
      toast.show('Promotion supprimée.', { variant: 'success' });
    } catch (e) {
      const message = getHttpErrorMessage(e, 'Suppression impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    }
  };
  return {
    promotions,
    audiences,
    query,
    setQuery,
    statusFilter,
    setStatusFilter,
    loading,
    error,
    filteredPromotions,
    handleDelete,
  };
};
