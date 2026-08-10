String resolveAbsoluteUrl(
  String baseUrl,
  String? value,
) {
  final trimmed = value?.trim() ?? '';
  if (trimmed.isEmpty) return '';

  final uri = Uri.tryParse(trimmed);
  if (uri != null && uri.hasScheme) {
    return uri.toString();
  }

  return Uri.parse(baseUrl).resolve(trimmed).toString();
}
