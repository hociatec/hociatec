import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class TradeInMetadataOption {
  const TradeInMetadataOption({
    required this.value,
    required this.label,
  });

  final String value;
  final String label;
}

class TradeInMetadata {
  const TradeInMetadata({
    required this.categories,
    required this.conditions,
  });

  final List<TradeInMetadataOption> categories;
  final List<TradeInMetadataOption> conditions;
}

class TradeInRepository {
  const TradeInRepository(this._client);

  final ApiClient _client;

  Future<TradeInMetadata> fetchMetadata() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/trade-ins/metadata',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les metadonnees de reprise.',
    );

    return TradeInMetadata(
      categories: _readOptions(payload['categories']),
      conditions: _readOptions(payload['conditions']),
    );
  }

  Future<String> create({
    required String firstName,
    required String lastName,
    required String email,
    required String phone,
    required String category,
    required String productName,
    required int purchasePriceCents,
    required int purchaseYear,
    required String brand,
    required String model,
    required String serialNumber,
    required String conditionGrade,
    required bool functional,
    required bool hasAccessories,
    required bool hasProofOfPurchase,
    required String description,
    required bool consent,
    required PlatformFile rib,
  }) async {
    final formData = FormData.fromMap(<String, dynamic>{
      'firstName': firstName,
      'lastName': lastName,
      'email': email,
      'phone': phone,
      'category': category,
      'productName': productName,
      'purchasePriceCents': purchasePriceCents,
      'purchaseYear': purchaseYear,
      'brand': brand,
      'model': model,
      'serialNumber': serialNumber,
      'conditionGrade': conditionGrade,
      'functional': functional.toString(),
      'hasAccessories': hasAccessories.toString(),
      'hasProofOfPurchase': hasProofOfPurchase.toString(),
      'description': description,
      'catalogProductId': '',
      'consent': consent.toString(),
      'rib': MultipartFile.fromBytes(
        rib.bytes!,
        filename: rib.name,
      ),
    });

    final response = await _client.post<Map<String, dynamic>>(
      '/api/public/trade-ins',
      data: formData,
      options: Options(
        headers: const <String, String>{
          'Content-Type': 'multipart/form-data',
        },
      ),
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Votre demande de reprise a bien ete enregistree.';
  }

  List<TradeInMetadataOption> _readOptions(Object? raw) {
    if (raw is! List) {
      return const <TradeInMetadataOption>[];
    }

    return raw
        .whereType<Map>()
        .map((item) {
          final map = item.cast<String, dynamic>();
          return TradeInMetadataOption(
            value: (map['value'] as String?)?.trim() ?? '',
            label: (map['label'] as String?)?.trim() ?? '',
          );
        })
        .where((item) => item.value.isNotEmpty)
        .toList(growable: false);
  }
}

final tradeInRepositoryProvider = Provider<TradeInRepository>((ref) {
  return TradeInRepository(ref.watch(apiClientProvider));
});

final tradeInMetadataProvider = FutureProvider<TradeInMetadata>((ref) {
  return ref.watch(tradeInRepositoryProvider).fetchMetadata();
});
