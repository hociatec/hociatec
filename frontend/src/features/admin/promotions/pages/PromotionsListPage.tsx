import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { deletePromotion, fetchPromotionAudiences, fetchPromotions, type Promotion } from '@/features/admin/promotions/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

const formatDiscount = (promotion: Promotion) =>
  promotion.discountType === 'percent'
    ? `${promotion.discountValue}%`
    : formatEuroCents(promotion.discountValue);

export const PromotionsListPage = () => {
  useDocumentTitle('Admin - Promotions');
  const toast = useToast();
  const confirm = useConfirm();
  const [promotions, setPromotions] = useState<Promotion[]>([]);
  const [audiences, setAudiences] = useState<Record<string, { label: string; description: string }>>({});
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void Promise.all([fetchPromotions(), fetchPromotionAudiences()])
      .then(([promotionsList, audienceList]) => {
        setPromotions(promotionsList);
        setAudiences(audienceList);
      })
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de charger les promotions.')))
      .finally(() => setLoading(false));
  }, []);

  const filteredPromotions = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return promotions.filter((promotion) => {
      const matchesQuery = normalizedQuery.length === 0
        || promotion.name.toLowerCase().includes(normalizedQuery)
        || promotion.slug.toLowerCase().includes(normalizedQuery);
      const matchesStatus = statusFilter === 'all'
        || (statusFilter === 'active' && promotion.isActive)
        || (statusFilter === 'inactive' && !promotion.isActive);

      return matchesQuery && matchesStatus;
    });
  }, [promotions, query, statusFilter]);

  const handleDelete = async (promotionId: number) => {
    const promotion = promotions.find((item) => item.id === promotionId);
    const promotionLabel = promotion ? `"${promotion.name}" (${promotion.slug})` : 'cette promotion';

    const confirmed = await confirm({
      title: 'Supprimer la promotion',
      description: `Supprimer ${promotionLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) return;
    try {
      await deletePromotion(promotionId);
      setPromotions((prev) => prev.filter((item) => item.id !== promotionId));
      toast.show('Promotion supprimée.', { variant: 'success' });
    } catch (err) {
      const message = getHttpErrorMessage(err, 'Suppression impossible.');
      setError(message);
      toast.show(message, { variant: 'error' });
    }
  };

  return (
    <PageContainer size="admin"
      title="Promotions"
      headerActions={
        <div className="flex gap-3">
          <PrimaryLink to="/admin/promotions/new">
            Nouvelle promotion
          </PrimaryLink>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Créez des remises automatiques.
        </p>
        <p className="text-sm text-stone-500">
          La meilleure promotion éligible est appliquée automatiquement dans le panier.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <div className="mb-6 grid gap-4 rounded-xl border border-brand-100 bg-white p-5 shadow-sm md:grid-cols-2">
        <label className="register-form__field">
          <span className="register-form__label">Recherche</span>
          <input className="register-form__input" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Nom ou slug..." />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Statut</span>
          <select className="register-form__input" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
            <option value="all">Toutes</option>
            <option value="active">Actives</option>
            <option value="inactive">Inactives</option>
          </select>
        </label>
      </div>

      <AdminListState
        loading={loading}
        isEmpty={filteredPromotions.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucune promotion."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Remise</th>
                <th scope="col">Audience</th>
                <th scope="col">Statut</th>
                <th scope="col">Validité</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredPromotions.map((promotion) => (
                <tr key={promotion.id}>
                  <th scope="row">
                    <strong>{promotion.name}</strong>
                    <div className="muted">{promotion.slug}</div>
                  </th>
                  <td>{formatDiscount(promotion)}</td>
                  <td>{audiences[promotion.audienceKey]?.label ?? promotion.audienceKey}</td>
                  <td>{promotion.isActive ? 'Active' : 'Inactive'}</td>
                  <td>
                    {promotion.startsAt ? formatOptionalFrenchDate(promotion.startsAt) : 'Immédiat'}
                    {' - '}
                    {promotion.endsAt ? formatOptionalFrenchDate(promotion.endsAt) : 'Sans fin'}
                  </td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/promotions/${promotion.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la promotion ${promotion.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(promotion.id)}
                        aria-label={`Supprimer la promotion ${promotion.name}`}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
