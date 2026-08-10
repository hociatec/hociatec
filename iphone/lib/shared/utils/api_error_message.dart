import 'package:dio/dio.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';

String resolveApiErrorMessage(
  Object error,
  String fallback,
) {
  if (error is ApiException) {
    return error.message;
  }

  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = (data['message'] as String?)?.trim();
      if (message != null && message.isNotEmpty) {
        return message;
      }
    }

    if (error.response?.statusCode == 401) {
      return 'Connexion requise pour continuer.';
    }
  }

  return fallback;
}
