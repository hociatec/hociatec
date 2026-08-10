class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => 'ApiException($statusCode): $message';
}

Map<String, dynamic> requireApiMap(
  Object? raw,
  String fallbackMessage,
) {
  if (raw is Map<String, dynamic>) {
    return raw;
  }

  throw ApiException(fallbackMessage);
}

Map<String, dynamic> unwrapApiDataMap(
  Object? raw,
  String fallbackMessage,
) {
  final envelope = requireApiMap(raw, fallbackMessage);
  final data = envelope['data'];

  if (data is Map<String, dynamic>) {
    return data;
  }

  throw ApiException((envelope['message'] as String?) ?? fallbackMessage);
}

List<Map<String, dynamic>> readItemList(
  Map<String, dynamic> payload,
  String fallbackMessage,
) {
  final items = payload['items'];

  if (items is! List) {
    throw ApiException(fallbackMessage);
  }

  return items
      .whereType<Map>()
      .map((item) => item.cast<String, dynamic>())
      .toList(growable: false);
}
