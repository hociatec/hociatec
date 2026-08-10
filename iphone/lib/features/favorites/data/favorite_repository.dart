import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/config/app_config_provider.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/shared/utils/url_utils.dart';

class FavoriteItem {
  const FavoriteItem({
    required this.addedAt,
    required this.product,
  });

  factory FavoriteItem.fromJson(
    Map<String, dynamic> json, {
    required String baseUrl,
  }) {
    final productJson = (json['product'] as Map?)?.cast<String, dynamic>() ??
        const <String, dynamic>{};
    final normalized = Map<String, dynamic>.from(productJson);
    normalized['imageUrl'] =
        resolveAbsoluteUrl(baseUrl, productJson['imageUrl'] as String?);
    normalized['gallery'] =
        ((productJson['gallery'] as List?) ?? const <dynamic>[])
            .whereType<Map>()
            .map((entry) {
      final mapped = Map<String, dynamic>.from(entry.cast<String, dynamic>());
      mapped['url'] = resolveAbsoluteUrl(baseUrl, mapped['url'] as String?);
      return mapped;
    }).toList(growable: false);

    return FavoriteItem(
      addedAt: (json['addedAt'] as String?)?.trim() ?? '',
      product: CatalogProduct.fromJson(normalized, baseUrl: baseUrl),
    );
  }

  final String addedAt;
  final CatalogProduct product;
}

class FavoriteRepository {
  const FavoriteRepository(this._client, this._baseUrl);

  final ApiClient _client;
  final String _baseUrl;

  Future<List<FavoriteItem>> fetchFavorites() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/favorites',
      queryParameters: const <String, dynamic>{'perPage': 20},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos favoris.',
    );

    return readItemList(payload, 'Impossible de charger vos favoris.')
        .map((item) => FavoriteItem.fromJson(item, baseUrl: _baseUrl))
        .toList(growable: false);
  }
}

final favoriteRepositoryProvider = Provider<FavoriteRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  final config = ref.watch(appConfigProvider);
  return FavoriteRepository(client, config.apiBaseUrl);
});

final myFavoritesProvider = FutureProvider<List<FavoriteItem>>((ref) {
  return ref.watch(favoriteRepositoryProvider).fetchFavorites();
});
