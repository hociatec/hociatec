import { Heart } from 'lucide-react';

import { useToast } from '@/shared/components/ui/toast';
import { useFavoriteItem } from '@/features/favorites/hooks/useFavoriteItem';
import type { FavoriteCategory } from '@/features/favorites/api/favoritesApi';

const FAVORITE_LABELS: Record<FavoriteCategory, string> = {
  product: 'produit',
  service: 'service',
  news: 'actualité',
  podcast: 'podcast',
};

const capitalizeLabel = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);

export const FavoriteToggleButton = ({
  category,
  targetId,
  className = '',
}: {
  category: FavoriteCategory;
  targetId: number;
  className?: string;
}) => {
  const { show } = useToast();
  const favorite = useFavoriteItem(category, targetId);
  const label = favorite.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';

  const handleClick = () => {
    void favorite.toggle()
      .then(({ alreadyFavorite }) => {
        if (favorite.isFavorite) {
          show(`${FAVORITE_LABELS[category]} retiré des favoris.`);
          return;
        }

        show(
          alreadyFavorite
            ? `Ce ${FAVORITE_LABELS[category]} est déjà présent dans vos favoris.`
            : `${capitalizeLabel(FAVORITE_LABELS[category])} ajouté aux favoris.`,
        );
      })
      .catch((error: unknown) => {
        show(
          error instanceof Error ? error.message : "Impossible de mettre à jour ce favori.",
          { variant: 'error' },
        );
      });
  };

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={!favorite.isAuthenticated || favorite.favoriteAction === 'saving'}
      aria-label={label}
      title={label}
      className={`inline-flex items-center justify-center rounded-full border px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${
        favorite.isFavorite
          ? 'border-red-300 bg-red-50 text-red-600 hover:border-red-400'
          : 'border-brand-200 bg-white text-stone-700 hover:border-brand-500'
      } ${className}`.trim()}
    >
      <Heart className={favorite.isFavorite ? 'fill-current' : ''} size={16} />
    </button>
  );
};
