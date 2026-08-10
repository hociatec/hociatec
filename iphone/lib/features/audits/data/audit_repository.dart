import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class AuditTypeOption {
  const AuditTypeOption({
    required this.value,
    required this.label,
  });

  final String value;
  final String label;
}

class AuditListItem {
  const AuditListItem({
    required this.id,
    required this.number,
    required this.typeLabel,
    required this.statusLabel,
    required this.url,
    required this.createdAt,
  });

  factory AuditListItem.fromJson(Map<String, dynamic> json) {
    return AuditListItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      number: (json['number'] as String?)?.trim() ?? '',
      typeLabel: (json['typeLabel'] as String?)?.trim() ?? '',
      statusLabel: (json['statusLabel'] as String?)?.trim() ?? '',
      url: (json['url'] as String?)?.trim() ?? '',
      createdAt: (json['createdAt'] as String?)?.trim() ?? '',
    );
  }

  final int id;
  final String number;
  final String typeLabel;
  final String statusLabel;
  final String url;
  final String createdAt;
}

class AuditRepository {
  const AuditRepository(this._client);

  final ApiClient _client;

  static const List<AuditTypeOption> defaultTypes = <AuditTypeOption>[
    AuditTypeOption(value: 'performance', label: 'Performance'),
    AuditTypeOption(value: 'security', label: 'Securite'),
    AuditTypeOption(value: 'ux', label: 'Experience utilisateur'),
    AuditTypeOption(value: 'seo', label: 'SEO'),
    AuditTypeOption(value: 'technical', label: 'Technique'),
    AuditTypeOption(value: 'accessibility', label: 'Accessibilite'),
  ];

  Future<List<AuditTypeOption>> fetchTypes() async {
    final response =
        await _client.get<Map<String, dynamic>>('/api/audits/metadata');
    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les types d audit.',
    );
    final rawTypes = payload['types'];
    if (rawTypes is! List) {
      return defaultTypes;
    }

    return rawTypes
        .whereType<Map>()
        .map((item) {
          final map = item.cast<String, dynamic>();
          return AuditTypeOption(
            value: (map['value'] as String?)?.trim() ?? '',
            label: (map['label'] as String?)?.trim() ?? '',
          );
        })
        .where((item) => item.value.isNotEmpty)
        .toList(growable: false);
  }

  Future<String> createAudit({
    required String type,
    required String url,
    String? objectives,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/api/audits',
      data: <String, dynamic>{
        'type': type,
        'url': url,
        'objectives': objectives,
      },
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Votre demande d audit a bien ete enregistree.';
  }

  Future<List<AuditListItem>> fetchMyAudits() async {
    final response = await _client.get<Map<String, dynamic>>('/api/audits');
    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos audits.',
    );

    return readItemList(payload, 'Impossible de charger vos audits.')
        .map(AuditListItem.fromJson)
        .toList(growable: false);
  }
}

final auditRepositoryProvider = Provider<AuditRepository>((ref) {
  return AuditRepository(ref.watch(apiClientProvider));
});

final auditTypesProvider = FutureProvider<List<AuditTypeOption>>((ref) async {
  try {
    return await ref.watch(auditRepositoryProvider).fetchTypes();
  } catch (_) {
    return AuditRepository.defaultTypes;
  }
});

final myAuditsProvider = FutureProvider<List<AuditListItem>>((ref) {
  return ref.watch(auditRepositoryProvider).fetchMyAudits();
});
