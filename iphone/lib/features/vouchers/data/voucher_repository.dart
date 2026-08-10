import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class VoucherItem {
  const VoucherItem({
    required this.id,
    required this.name,
    required this.code,
    required this.discountType,
    required this.discountValue,
    required this.isActive,
    required this.createdAt,
    required this.description,
    required this.endsAt,
  });

  factory VoucherItem.fromJson(Map<String, dynamic> json) {
    return VoucherItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?)?.trim() ?? '',
      code: (json['code'] as String?)?.trim() ?? '',
      discountType: (json['discountType'] as String?)?.trim() ?? '',
      discountValue: (json['discountValue'] as num?)?.toDouble() ?? 0,
      isActive: json['isActive'] as bool? ?? false,
      createdAt: (json['createdAt'] as String?)?.trim() ?? '',
      description: (json['description'] as String?)?.trim(),
      endsAt: (json['endsAt'] as String?)?.trim(),
    );
  }

  final int id;
  final String name;
  final String code;
  final String discountType;
  final double discountValue;
  final bool isActive;
  final String createdAt;
  final String? description;
  final String? endsAt;

  String get valueLabel {
    if (discountType == 'percent') {
      return '${discountValue.toStringAsFixed(discountValue.truncateToDouble() == discountValue ? 0 : 1)} %';
    }

    return '${(discountValue / 100).toStringAsFixed(2)} €';
  }
}

class VoucherRepository {
  const VoucherRepository(this._client);

  final ApiClient _client;

  Future<List<VoucherItem>> fetchMyVouchers() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/vouchers/me',
      queryParameters: const <String, dynamic>{'perPage': 10},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos bons.',
    );

    return readItemList(payload, 'Impossible de charger vos bons.')
        .map(VoucherItem.fromJson)
        .toList(growable: false);
  }
}

final voucherRepositoryProvider = Provider<VoucherRepository>((ref) {
  return VoucherRepository(ref.watch(apiClientProvider));
});

final myVouchersProvider = FutureProvider<List<VoucherItem>>((ref) {
  return ref.watch(voucherRepositoryProvider).fetchMyVouchers();
});
