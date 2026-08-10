import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class MyTradeInItem {
  const MyTradeInItem({
    required this.id,
    required this.reference,
    required this.statusLabel,
    required this.categoryLabel,
    required this.productName,
    required this.createdAt,
    required this.offerCents,
  });

  factory MyTradeInItem.fromJson(Map<String, dynamic> json) {
    return MyTradeInItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      reference: (json['reference'] as String?)?.trim() ?? '',
      statusLabel: (json['statusLabel'] as String?)?.trim() ?? '',
      categoryLabel: (json['categoryLabel'] as String?)?.trim() ?? '',
      productName: (json['productName'] as String?)?.trim() ?? '',
      createdAt: (json['createdAt'] as String?)?.trim() ?? '',
      offerCents: (json['offerCents'] as num?)?.toInt(),
    );
  }

  final int id;
  final String reference;
  final String statusLabel;
  final String categoryLabel;
  final String productName;
  final String createdAt;
  final int? offerCents;
}

class MyTradeInRepository {
  const MyTradeInRepository(this._client);

  final ApiClient _client;

  Future<List<MyTradeInItem>> fetchMyTradeIns() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/trade-ins/me',
      queryParameters: const <String, dynamic>{'perPage': 10},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos reprises.',
    );

    return readItemList(payload, 'Impossible de charger vos reprises.')
        .map(MyTradeInItem.fromJson)
        .toList(growable: false);
  }
}

final myTradeInRepositoryProvider = Provider<MyTradeInRepository>((ref) {
  return MyTradeInRepository(ref.watch(apiClientProvider));
});

final myTradeInsProvider = FutureProvider<List<MyTradeInItem>>((ref) {
  return ref.watch(myTradeInRepositoryProvider).fetchMyTradeIns();
});
