import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class OrderListItem {
  const OrderListItem({
    required this.id,
    required this.number,
    required this.statusLabel,
    required this.totalPriceCents,
    required this.createdAt,
    required this.itemCount,
  });

  factory OrderListItem.fromJson(Map<String, dynamic> json) {
    final items = (json['items'] as List?) ?? const <dynamic>[];

    return OrderListItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      number: (json['number'] as String?)?.trim() ?? '',
      statusLabel: (json['statusLabel'] as String?)?.trim() ?? '',
      totalPriceCents: (json['totalPriceCents'] as num?)?.toInt() ?? 0,
      createdAt: (json['createdAt'] as String?)?.trim() ?? '',
      itemCount: items.length,
    );
  }

  final int id;
  final String number;
  final String statusLabel;
  final int totalPriceCents;
  final String createdAt;
  final int itemCount;
}

class OrderRepository {
  const OrderRepository(this._client);

  final ApiClient _client;

  Future<List<OrderListItem>> fetchMyOrders() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/orders/me',
      queryParameters: const <String, dynamic>{'perPage': 10},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos commandes.',
    );

    return readItemList(payload, 'Impossible de charger vos commandes.')
        .map(OrderListItem.fromJson)
        .toList(growable: false);
  }
}

final orderRepositoryProvider = Provider<OrderRepository>((ref) {
  return OrderRepository(ref.watch(apiClientProvider));
});

final myOrdersProvider = FutureProvider<List<OrderListItem>>((ref) {
  return ref.watch(orderRepositoryProvider).fetchMyOrders();
});
