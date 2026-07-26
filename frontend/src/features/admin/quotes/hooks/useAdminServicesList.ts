import { useEffect, useMemo, useState } from 'react';
import { deleteAdminQuoteService, fetchAdminQuoteServices } from '@/features/quotes/api/quotesApi';
import type { QuoteServiceDto } from '@/features/quotes/types/quoteTypes';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
export const formatServiceDuration = (service: QuoteServiceDto) =>
  !service.durationValue || !service.durationUnit
    ? '—'
    : `${service.durationValue} ${service.durationUnit === 'day' ? 'jour' : 'heure'}${service.durationValue > 1 ? 's' : ''}`;
export const useAdminServicesList = () => {
  const confirm = useConfirm();
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  useEffect(() => {
    void fetchAdminQuoteServices()
      .then(setServices)
      .catch((e) => setError(getHttpErrorMessage(e, 'Chargement impossible.')))
      .finally(() => setLoading(false));
  }, []);
  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return services.filter((service) => !term || service.title.toLowerCase().includes(term));
  }, [services, search]);
  const handleDelete = async (id: number) => {
    const service = services.find((item) => item.id === id);
    if (
      !(await confirm({
        title: 'Supprimer le service',
        description: `Supprimer ${service ? `"${service.title}"` : 'ce service'} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    try {
      await deleteAdminQuoteService(id);
      setServices((items) => items.filter((item) => item.id !== id));
      setMessage('Service supprimé.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Suppression impossible.'));
    }
  };
  return { loading, error, message, search, setSearch, filtered, handleDelete };
};
