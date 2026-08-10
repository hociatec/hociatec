import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/favorites/data/favorite_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class FavoritesScreen extends ConsumerWidget {
  const FavoritesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final favoritesAsync = ref.watch(myFavoritesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes favoris')),
      body: AsyncCollectionView<FavoriteItem>(
        asyncValue: favoritesAsync,
        emptyMessage: 'Aucun favori enregistre.',
        errorFallback: 'Impossible de charger vos favoris.',
        itemBuilder: (context, favorite) => Card(
          child: ListTile(
            contentPadding: const EdgeInsets.all(16),
            title: Text(
              favorite.product.name,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
            subtitle: Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                '${formatPriceCents(favorite.product.effectivePriceCents)} • ajoute le ${formatIsoDate(favorite.addedAt)}',
              ),
            ),
            onTap: () =>
                context.push('/catalogue/produits/${favorite.product.slug}'),
          ),
        ),
      ),
    );
  }
}
