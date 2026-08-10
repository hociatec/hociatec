import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';

class QuoteRepository {
  const QuoteRepository(this._client);

  final ApiClient _client;

  Future<List<ServiceOffering>> fetchPublicServices() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/services',
      queryParameters: const <String, dynamic>{'perPage': 20},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les services pour le devis.',
    );

    return readItemList(
            payload, 'Impossible de charger les services pour le devis.')
        .map(ServiceOffering.fromJson)
        .toList(growable: false);
  }

  Future<String> createQuote({
    required String customerName,
    required String customerEmail,
    String? customerCompany,
    String? customerAddress,
    required String requestTitle,
    String? requestDescription,
    int? selectedServiceId,
  }) async {
    final item = selectedServiceId != null
        ? <String, dynamic>{
            'type': 'service',
            'serviceId': selectedServiceId,
            'name': requestTitle,
            'description': requestDescription,
            'quantity': 1,
            'unitPriceCents': 0,
            'vatRate': 20,
            'discountCents': 0,
          }
        : <String, dynamic>{
            'type': 'custom',
            'name': requestTitle,
            'description': requestDescription,
            'quantity': 1,
            'unitPriceCents': 0,
            'vatRate': 20,
            'discountCents': 0,
          };

    final response = await _client.post<Map<String, dynamic>>(
      '/api/public/quotes',
      data: <String, dynamic>{
        'customer': <String, dynamic>{
          'name': customerName,
          'email': customerEmail,
          'company': customerCompany,
          'address': customerAddress,
        },
        'items': <Map<String, dynamic>>[item],
      },
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Votre devis a bien ete enregistre.';
  }
}

final quoteRepositoryProvider = Provider<QuoteRepository>((ref) {
  return QuoteRepository(ref.watch(apiClientProvider));
});

final publicQuoteServicesProvider =
    FutureProvider<List<ServiceOffering>>((ref) {
  return ref.watch(quoteRepositoryProvider).fetchPublicServices();
});
