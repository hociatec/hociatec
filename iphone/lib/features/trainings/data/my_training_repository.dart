import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class MyTrainingEnrollment {
  const MyTrainingEnrollment({
    required this.id,
    required this.title,
    required this.statusLabel,
    required this.priceCents,
    required this.scheduledStartsAt,
    required this.formatLabel,
  });

  factory MyTrainingEnrollment.fromJson(Map<String, dynamic> json) {
    final session = (json['session'] as Map?)?.cast<String, dynamic>() ??
        const <String, dynamic>{};
    final training = (session['training'] as Map?)?.cast<String, dynamic>() ??
        const <String, dynamic>{};

    return MyTrainingEnrollment(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: (training['title'] as String?)?.trim() ?? '',
      statusLabel: (json['statusLabel'] as String?)?.trim() ?? '',
      priceCents: (json['priceCents'] as num?)?.toInt() ?? 0,
      scheduledStartsAt: (json['scheduledStartsAt'] as String?)?.trim() ?? '',
      formatLabel: (session['formatLabel'] as String?)?.trim() ?? '',
    );
  }

  final int id;
  final String title;
  final String statusLabel;
  final int priceCents;
  final String scheduledStartsAt;
  final String formatLabel;
}

class MyTrainingRepository {
  const MyTrainingRepository(this._client);

  final ApiClient _client;

  Future<List<MyTrainingEnrollment>> fetchMyEnrollments() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/trainings/enrollments/me',
      queryParameters: const <String, dynamic>{'perPage': 10},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos formations.',
    );

    return readItemList(payload, 'Impossible de charger vos formations.')
        .map(MyTrainingEnrollment.fromJson)
        .toList(growable: false);
  }
}

final myTrainingRepositoryProvider = Provider<MyTrainingRepository>((ref) {
  return MyTrainingRepository(ref.watch(apiClientProvider));
});

final myTrainingEnrollmentsProvider =
    FutureProvider<List<MyTrainingEnrollment>>((ref) {
  return ref.watch(myTrainingRepositoryProvider).fetchMyEnrollments();
});
