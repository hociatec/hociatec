import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/config/app_config_provider.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:hociatec_mobile/shared/utils/url_utils.dart';

class ServicesRepository {
  const ServicesRepository(this._client, this._baseUrl);

  final ApiClient _client;
  final String _baseUrl;

  Future<List<ServiceOffering>> fetchAll() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/services',
      queryParameters: const <String, dynamic>{'perPage': 20},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les services.',
    );

    return readItemList(payload, 'Impossible de charger les services.')
        .map((item) => ServiceOffering.fromJson(_normalize(item)))
        .toList(growable: false);
  }

  Future<ServiceOffering> fetchById(int id) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/services/$id',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Service introuvable.',
    );

    return ServiceOffering.fromJson(_normalize(payload));
  }

  Map<String, dynamic> _normalize(Map<String, dynamic> item) {
    final normalized = Map<String, dynamic>.from(item);
    normalized['imageUrl'] = resolveAbsoluteUrl(_baseUrl, item['imageUrl'] as String?);
    return normalized;
  }
}

final servicesRepositoryProvider = Provider<ServicesRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  final config = ref.watch(appConfigProvider);
  return ServicesRepository(client, config.apiBaseUrl);
});

final allServicesProvider = FutureProvider<List<ServiceOffering>>((ref) {
  return ref.watch(servicesRepositoryProvider).fetchAll();
});

final featuredServicesProvider = FutureProvider<List<ServiceOffering>>((ref) async {
  final services = await ref.watch(servicesRepositoryProvider).fetchAll();
  final featured = services.where((service) => service.isFeaturedHome).toList();
  return featured.isNotEmpty ? featured.take(6).toList(growable: false) : services.take(6).toList(growable: false);
});

final serviceDetailProvider =
    FutureProvider.family<ServiceOffering, int>((ref, id) {
  return ref.watch(servicesRepositoryProvider).fetchById(id);
});
