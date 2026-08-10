String formatPriceCents(int value, {String? suffix}) {
  final euros = value ~/ 100;
  final cents = value.abs() % 100;
  final formatted = '$euros,${cents.toString().padLeft(2, '0')} EUR';

  if (suffix == null || suffix.isEmpty) {
    return formatted;
  }

  return '$formatted $suffix';
}

String extractFirstSentence(String? value, String fallback) {
  final normalized = stripBasicHtml(value).trim();
  if (normalized.isEmpty) return fallback;

  final match = RegExp(r'[^.!?]+[.!?]?').firstMatch(normalized);
  return (match?.group(0) ?? normalized).trim();
}

String stripBasicHtml(String? value) {
  if (value == null || value.trim().isEmpty) return '';

  return value
      .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</p>', caseSensitive: false), '\n\n')
      .replaceAll(RegExp(r'<[^>]*>'), '')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&amp;', '&')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .trim();
}

String formatIsoDate(String? value) {
  if (value == null || value.isEmpty) return 'Date non definie';

  final date = DateTime.tryParse(value)?.toLocal();
  if (date == null) return value;

  const months = <String>[
    'janv.',
    'fevr.',
    'mars',
    'avr.',
    'mai',
    'juin',
    'juil.',
    'aout',
    'sept.',
    'oct.',
    'nov.',
    'dec.',
  ];

  return '${date.day} ${months[date.month - 1]} ${date.year}';
}
