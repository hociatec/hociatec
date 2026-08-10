import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/config/app_config_provider.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/shared/utils/url_utils.dart';

class CatalogRepository {
  const CatalogRepository(this._client, this._baseUrl);

  final ApiClient _client;
  final String _baseUrl;

  Future<List<CatalogProduct>> fetchFeaturedProducts({
    int perPage = 6,
  }) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/catalog/products',
      queryParameters: <String, dynamic>{
        'homepage': '1',
        'perPage': perPage,
      },
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les produits.',
    );

    return readItemList(payload, 'Impossible de charger les produits.')
        .map((item) => CatalogProduct.fromJson(
              _normalizeProductJson(item),
              baseUrl: _baseUrl,
            ))
        .toList(growable: false);
  }

  Future<CatalogProduct> fetchProduct(String slug) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/catalog/products/$slug',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Produit introuvable.',
    );

    return CatalogProduct.fromJson(
      _normalizeProductJson(payload),
      baseUrl: _baseUrl,
    );
  }

  Future<List<CatalogProduct>> fetchProductsBySellingType(
    String sellingType, {
    int perPage = 20,
  }) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/catalog/products',
      queryParameters: <String, dynamic>{
        'sellingType': sellingType,
        'perPage': perPage,
      },
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les produits du catalogue.',
    );

    return readItemList(
            payload, 'Impossible de charger les produits du catalogue.')
        .map((item) => CatalogProduct.fromJson(
              _normalizeProductJson(item),
              baseUrl: _baseUrl,
            ))
        .toList(growable: false);
  }

  Future<void> shareProductByEmail({
    required String slug,
    required String email,
  }) async {
    await _client.post<Map<String, dynamic>>(
      '/api/public/catalog/products/$slug/share',
      data: <String, dynamic>{'email': email},
    );
  }

  Map<String, dynamic> _normalizeProductJson(Map<String, dynamic> item) {
    final normalized = Map<String, dynamic>.from(item);
    normalized['imageUrl'] =
        resolveAbsoluteUrl(_baseUrl, item['imageUrl'] as String?);
    normalized['gallery'] = ((item['gallery'] as List?) ?? const <dynamic>[])
        .whereType<Map>()
        .map((entry) {
      final mapped = Map<String, dynamic>.from(entry.cast<String, dynamic>());
      mapped['url'] = resolveAbsoluteUrl(_baseUrl, mapped['url'] as String?);
      return mapped;
    }).toList(growable: false);
    return normalized;
  }
}

final catalogRepositoryProvider = Provider<CatalogRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  final config = ref.watch(appConfigProvider);
  return CatalogRepository(client, config.apiBaseUrl);
});

final featuredProductsProvider = FutureProvider<List<CatalogProduct>>((ref) {
  return ref.watch(catalogRepositoryProvider).fetchFeaturedProducts();
});

final siteBaseUrlProvider = Provider<String>((ref) {
  final config = ref.watch(appConfigProvider);
  return config.siteBaseUrl;
});

final productDetailProvider =
    FutureProvider.family<CatalogProduct, String>((ref, slug) {
  return ref.watch(catalogRepositoryProvider).fetchProduct(slug);
});

final catalogProductsBySellingTypeProvider =
    FutureProvider.family<List<CatalogProduct>, String>((ref, sellingType) {
  return ref
      .watch(catalogRepositoryProvider)
      .fetchProductsBySellingType(sellingType);
});
