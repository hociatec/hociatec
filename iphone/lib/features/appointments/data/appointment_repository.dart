import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

class AppointmentPrestation {
  const AppointmentPrestation({
    required this.id,
    required this.name,
    required this.durationMinutes,
    required this.priceCents,
  });

  factory AppointmentPrestation.fromJson(Map<String, dynamic> json) {
    return AppointmentPrestation(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?)?.trim() ?? '',
      durationMinutes: (json['durationMinutes'] as num?)?.toInt() ?? 0,
      priceCents: (json['priceCents'] as num?)?.toInt() ?? 0,
    );
  }

  final int id;
  final String name;
  final int durationMinutes;
  final int priceCents;
}

class AppointmentSlot {
  const AppointmentSlot({
    required this.start,
    required this.end,
  });

  factory AppointmentSlot.fromJson(Map<String, dynamic> json) {
    return AppointmentSlot(
      start: (json['start'] as String?)?.trim() ?? '',
      end: (json['end'] as String?)?.trim() ?? '',
    );
  }

  final String start;
  final String end;
}

class AppointmentItem {
  const AppointmentItem({
    required this.id,
    required this.startAt,
    required this.endAt,
    required this.status,
    required this.prestation,
  });

  factory AppointmentItem.fromJson(Map<String, dynamic> json) {
    return AppointmentItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      startAt: (json['startAt'] as String?)?.trim() ?? '',
      endAt: (json['endAt'] as String?)?.trim() ?? '',
      status: (json['status'] as String?)?.trim() ?? '',
      prestation: AppointmentPrestation.fromJson(
        (json['prestation'] as Map?)?.cast<String, dynamic>() ??
            const <String, dynamic>{},
      ),
    );
  }

  final int id;
  final String startAt;
  final String endAt;
  final String status;
  final AppointmentPrestation prestation;
}

class AppointmentRepository {
  const AppointmentRepository(this._client);

  final ApiClient _client;

  Future<List<AppointmentPrestation>> fetchPrestations() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/appointments/prestations',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les prestations.',
    );

    return readItemList(payload, 'Impossible de charger les prestations.')
        .map(AppointmentPrestation.fromJson)
        .toList(growable: false);
  }

  Future<List<AppointmentSlot>> fetchAvailability({
    required int prestationId,
    required DateTime start,
    required DateTime end,
  }) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/appointments/availability',
      queryParameters: <String, dynamic>{
        'prestationId': prestationId,
        'start': start.toUtc().toIso8601String(),
        'end': end.toUtc().toIso8601String(),
      },
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les creneaux.',
    );

    final slots = payload['slots'];
    if (slots is! List) {
      throw const ApiException('Impossible de charger les creneaux.');
    }

    return slots
        .whereType<Map>()
        .map((item) => AppointmentSlot.fromJson(item.cast<String, dynamic>()))
        .toList(growable: false);
  }

  Future<String> book({
    required int prestationId,
    required String startAt,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/api/appointments',
      data: <String, dynamic>{
        'prestationId': prestationId,
        'startAt': startAt,
      },
    );

    return (response.data?['message'] as String?)?.trim() ??
        'Votre rendez-vous a bien ete cree.';
  }

  Future<List<AppointmentItem>> fetchMyUpcomingAppointments() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/appointments/me',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger vos rendez-vous.',
    );

    final items = payload['upcoming'];
    if (items is! List) {
      throw const ApiException('Impossible de charger vos rendez-vous.');
    }

    return items
        .whereType<Map>()
        .map((item) => AppointmentItem.fromJson(item.cast<String, dynamic>()))
        .toList(growable: false);
  }
}

final appointmentRepositoryProvider = Provider<AppointmentRepository>((ref) {
  return AppointmentRepository(ref.watch(apiClientProvider));
});

final publicAppointmentPrestationsProvider =
    FutureProvider<List<AppointmentPrestation>>((ref) {
  return ref.watch(appointmentRepositoryProvider).fetchPrestations();
});

final myUpcomingAppointmentsProvider =
    FutureProvider<List<AppointmentItem>>((ref) {
  return ref.watch(appointmentRepositoryProvider).fetchMyUpcomingAppointments();
});
