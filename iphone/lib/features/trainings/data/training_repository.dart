import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class TrainingSummary {
  const TrainingSummary({
    required this.id,
    required this.title,
    required this.category,
    required this.durationMinutes,
    required this.priceCents,
    required this.shortDescription,
  });

  factory TrainingSummary.fromJson(Map<String, dynamic> json) {
    return TrainingSummary(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: (json['title'] as String?)?.trim() ?? '',
      category: (json['category'] as String?)?.trim() ?? '',
      durationMinutes: (json['durationMinutes'] as num?)?.toInt() ?? 0,
      priceCents: (json['priceCents'] as num?)?.toInt() ?? 0,
      shortDescription: (json['shortDescription'] as String?)?.trim(),
    );
  }

  final int id;
  final String title;
  final String category;
  final int durationMinutes;
  final int priceCents;
  final String? shortDescription;
}

class TrainingRepository {
  const TrainingRepository(this._client);

  final ApiClient _client;

  Future<List<TrainingSummary>> fetchPublicTrainings() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/trainings',
      queryParameters: const <String, dynamic>{'perPage': 20},
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les formations.',
    );

    return readItemList(payload, 'Impossible de charger les formations.')
        .map(TrainingSummary.fromJson)
        .toList(growable: false);
  }
}

final trainingRepositoryProvider = Provider<TrainingRepository>((ref) {
  return TrainingRepository(ref.watch(apiClientProvider));
});

final publicTrainingsProvider = FutureProvider<List<TrainingSummary>>((ref) {
  return ref.watch(trainingRepositoryProvider).fetchPublicTrainings();
});
